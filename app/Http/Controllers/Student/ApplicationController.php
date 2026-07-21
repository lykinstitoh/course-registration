<?php

namespace App\Http\Controllers\Student;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Intake;
use App\Models\Programme;
use App\Services\AcademicRules\AcademicRulesEngine;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    private const KENYAN_COUNTIES = [
        'Baringo', 'Bomet', 'Bungoma', 'Busia', 'Elgeyo-Marakwet', 'Embu',
        'Garissa', 'Homa Bay', 'Isiolo', 'Kajiado', 'Kakamega', 'Kericho',
        'Kiambu', 'Kilifi', 'Kirinyaga', 'Kisii', 'Kisumu', 'Kitui', 'Kwale',
        'Laikipia', 'Lamu', 'Machakos', 'Makueni', 'Mandera', 'Marsabit',
        'Meru', 'Migori', 'Mombasa', 'Murang\'a', 'Nairobi', 'Nakuru', 'Nandi',
        'Narok', 'Nyamira', 'Nyandarua', 'Nyeri', 'Samburu', 'Siaya', 'Taita-Taveta',
        'Tana River', 'Tharaka-Nithi', 'Trans Nzoia', 'Turkana', 'Uasin Gishu',
        'Vihiga', 'Wajir', 'West Pokot',
    ];

    public function __construct(
        private AcademicRulesEngine $rulesEngine,
        private NotificationService $notifications
    ) {}

    public function index()
    {
        $applications = Auth::user()->studentProfile
            ->applications()
            ->with(['programme', 'intake'])
            ->latest()
            ->get();

        return view('student.applications.index', compact('applications'));
    }

    public function create()
    {
        $programmes = Programme::where('is_active', true)->orderBy('name')->get();
        $intakes = Intake::where('is_active', true)
            ->where('application_closes', '>=', now())
            ->get();
        $campuses = \App\Models\Campus::where('is_active', true)->orderBy('name')->get();

        $latestKcseYear = now()->month >= 11 ? now()->year : now()->year - 1;
        $kcseYears = range($latestKcseYear, 1989);
        $counties = self::KENYAN_COUNTIES;

        return view('student.applications.create', compact('programmes', 'intakes', 'campuses', 'kcseYears', 'counties'));
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('action') === 'draft';

        $rules = [
            'programme_id' => [$isDraft ? 'nullable' : 'required', 'exists:programmes,id'],
            'intake_id' => [$isDraft ? 'nullable' : 'required', 'exists:intakes,id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'kcse_mean_grade' => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:1', 'max:12'],
            'kcse_index_number' => [$isDraft ? 'nullable' : 'required', 'string', 'max:30', 'regex:/^\d+$/'],
            'kcse_year' => [$isDraft ? 'nullable' : 'required', 'integer', Rule::in(range(now()->month >= 11 ? now()->year : now()->year - 1, 1989))],
            'national_id' => [$isDraft ? 'nullable' : 'required', 'string', 'max:20', 'regex:/^\d+$/'],
            'county' => [$isDraft ? 'nullable' : 'required', Rule::in(self::KENYAN_COUNTIES)],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:20'],
            'employment_details' => ['nullable', 'string'],
        ];

        $data = $request->validate($rules);

        $profile = Auth::user()->studentProfile;
        $profile->update([
            'kcse_mean_grade' => $data['kcse_mean_grade'] ?? $profile->kcse_mean_grade,
            'kcse_index_number' => $data['kcse_index_number'] ?? $profile->kcse_index_number,
            'kcse_year' => $data['kcse_year'] ?? $profile->kcse_year,
            'national_id' => $data['national_id'] ?? $profile->national_id,
            'county' => $data['county'] ?? $profile->county,
            'date_of_birth' => $data['date_of_birth'] ?? $profile->date_of_birth,
            'gender' => $data['gender'] ?? $profile->gender,
            'next_of_kin_name' => $data['next_of_kin_name'] ?? $profile->next_of_kin_name,
            'next_of_kin_phone' => $data['next_of_kin_phone'] ?? $profile->next_of_kin_phone,
            'employment_details' => $data['employment_details'] ?? $profile->employment_details,
        ]);

        if ($isDraft) {
            $application = Application::create([
                'reference' => 'APP-'.strtoupper(Str::random(8)),
                'student_profile_id' => $profile->id,
                'programme_id' => $data['programme_id'] ?? null,
                'intake_id' => $data['intake_id'] ?? null,
                'campus_id' => $data['campus_id'] ?? null,
                'status' => ApplicationStatus::Draft,
            ]);

            return redirect()->route('student.applications.index')
                ->with('success', 'Application saved as draft.');
        }

        $programme = Programme::findOrFail($data['programme_id']);
        $eligibility = $this->rulesEngine->checkKcseEligibility($profile, $programme);

        $application = Application::create([
            'reference' => 'APP-'.strtoupper(Str::random(8)),
            'student_profile_id' => $profile->id,
            'programme_id' => $data['programme_id'],
            'intake_id' => $data['intake_id'],
            'campus_id' => $data['campus_id'] ?? null,
            'status' => $eligibility['eligible']
                ? ApplicationStatus::Submitted
                : ApplicationStatus::Rejected,
            'rejection_reason' => $eligibility['eligible'] ? null : $eligibility['message'],
            'submitted_at' => now(),
        ]);

        $this->notifications->notifyApplicationStatus(
            Auth::user(),
            $application->status->label(),
            $application->reference
        );

        return redirect()->route('student.applications.index')
            ->with($eligibility['eligible'] ? 'success' : 'error', $eligibility['message']);
    }
}

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

        $latestKcseYear = now()->month >= 11 ? now()->year : now()->year - 1;
        $kcseYears = range($latestKcseYear, 1989);
        $counties = self::KENYAN_COUNTIES;

        return view('student.applications.create', compact('programmes', 'intakes', 'kcseYears', 'counties'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'programme_id' => ['required', 'exists:programmes,id'],
            'intake_id' => ['required', 'exists:intakes,id'],
            'kcse_mean_grade' => ['required', 'numeric', 'min:1', 'max:12'],
            'kcse_index_number' => ['required', 'string', 'max:30', 'regex:/^\d+$/'],
            'kcse_year' => ['required', 'integer', Rule::in(range(now()->month >= 11 ? now()->year : now()->year - 1, 1989))],
            'national_id' => ['required', 'string', 'max:20', 'regex:/^\d+$/'],
            'county' => ['required', Rule::in(self::KENYAN_COUNTIES)],
        ]);

        $profile = Auth::user()->studentProfile;
        $programme = Programme::findOrFail($data['programme_id']);

        $profile->update([
            'kcse_mean_grade' => $data['kcse_mean_grade'],
            'kcse_index_number' => $data['kcse_index_number'],
            'kcse_year' => $data['kcse_year'],
            'national_id' => $data['national_id'],
            'county' => $data['county'],
        ]);

        $eligibility = $this->rulesEngine->checkKcseEligibility($profile, $programme);

        $application = Application::create([
            'reference' => 'APP-'.strtoupper(Str::random(8)),
            'student_profile_id' => $profile->id,
            'programme_id' => $data['programme_id'],
            'intake_id' => $data['intake_id'],
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

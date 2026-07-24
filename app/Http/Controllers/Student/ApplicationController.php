<?php

namespace App\Http\Controllers\Student;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FeeStructure;
use App\Models\Intake;
use App\Models\Payment;
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

        // If rejected upfront, save as Rejected immediately
        if (!$eligibility['eligible']) {
            $application = Application::create([
                'reference' => 'APP-'.strtoupper(Str::random(8)),
                'student_profile_id' => $profile->id,
                'programme_id' => $data['programme_id'],
                'intake_id' => $data['intake_id'],
                'campus_id' => $data['campus_id'] ?? null,
                'status' => ApplicationStatus::Rejected,
                'rejection_reason' => $eligibility['message'],
                'submitted_at' => now(),
            ]);
            $this->notifications->notifyApplicationStatus(Auth::user(), $application->status->label(), $application->reference);
            return redirect()->route('student.applications.index')->with('error', $eligibility['message']);
        }

        // Look for specific programme fee first
        $appFee = FeeStructure::where('programme_id', $programme->id)
            ->where('intake_id', $data['intake_id'])
            ->where('fee_type', 'application')
            ->where('is_mandatory', true)
            ->first();

        // If no specific fee, look for an award_level fee
        if (!$appFee) {
            $appFee = FeeStructure::whereNull('programme_id')
                ->where('award_level', $programme->award_level)
                ->where('intake_id', $data['intake_id'])
                ->where('fee_type', 'application')
                ->where('is_mandatory', true)
                ->first();
        }

        // If still no fee, look for a universal fee
        if (!$appFee) {
            $appFee = FeeStructure::whereNull('programme_id')
                ->whereNull('award_level')
                ->where('intake_id', $data['intake_id'])
                ->where('fee_type', 'application')
                ->where('is_mandatory', true)
                ->first();
        }

        $hasPaid = false;
        if ($appFee) {
            $hasPaid = Payment::where('student_profile_id', $profile->id)
                ->where('fee_structure_id', $appFee->id)
                ->where('status', \App\Enums\PaymentStatus::Completed)
                ->exists();
        }

        $finalStatus = ($appFee && !$hasPaid) ? ApplicationStatus::PendingFee : ApplicationStatus::Submitted;

        $application = Application::create([
            'reference' => 'APP-'.strtoupper(Str::random(8)),
            'student_profile_id' => $profile->id,
            'programme_id' => $data['programme_id'],
            'intake_id' => $data['intake_id'],
            'campus_id' => $data['campus_id'] ?? null,
            'status' => $finalStatus,
            'submitted_at' => now(),
        ]);

        if ($finalStatus === ApplicationStatus::PendingFee) {
            return redirect()->route('student.payments.index')
                ->with('warning', 'Your application is saved, but you must pay the application fee before it can be submitted.');
        }

        $this->notifications->notifyApplicationStatus(
            Auth::user(),
            $application->status->label(),
            $application->reference
        );

        return redirect()->route('student.applications.index')
            ->with('success', 'Application submitted successfully.');
    }

    public function cancel(Application $application)
    {
        // Ensure the student owns the application
        if ($application->student_profile_id !== Auth::user()->studentProfile->id) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow cancelling if it's draft or pending fee
        if (!in_array($application->status, [ApplicationStatus::Draft, ApplicationStatus::PendingFee])) {
            return back()->with('error', 'You can only cancel draft or pending applications.');
        }

        $application->update([
            'status' => ApplicationStatus::Cancelled
        ]);

        return back()->with('success', 'Application cancelled successfully.');
    }

    public function downloadLetter(Application $application)
    {
        if ($application->student_profile_id !== Auth::user()->studentProfile->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($application->status !== ApplicationStatus::Approved) {
            return back()->with('error', 'Admission letter is only available for approved applications.');
        }

        $letter = \App\Models\AdmissionLetter::where('application_id', $application->id)->first();
        
        // Generate on the fly if missing or file doesn't exist
        if (!$letter || !\Illuminate\Support\Facades\Storage::disk('public')->exists($letter->letter_path)) {
            // Ensure they have an admission number first
            if (empty($application->studentProfile->admission_number)) {
                $application->studentProfile->update([
                    'admission_number' => config('ocrs.institution_code', 'OCRS').'-'.str_pad($application->id, 5, '0', STR_PAD_LEFT),
                ]);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.admission_letter', compact('application'));
            $path = 'letters/admission_'.$application->reference.'.pdf';
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());

            if (!$letter) {
                $letter = \App\Models\AdmissionLetter::create([
                    'student_profile_id' => $application->student_profile_id,
                    'application_id' => $application->id,
                    'letter_path' => $path,
                    'generated_at' => now(),
                ]);
            } else {
                $letter->update(['letter_path' => $path, 'generated_at' => now()]);
            }
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($letter->letter_path);
    }
}

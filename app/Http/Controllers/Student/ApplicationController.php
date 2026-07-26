<?php

namespace App\Http\Controllers\Student;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\FeeStructure;
use App\Models\Intake;
use App\Models\Payment;
use App\Models\Programme;
use App\Models\SystemSetting;
use App\Rules\KenyanIdentityNumber;
use App\Rules\KenyanPhoneNumber;
use App\Services\AcademicRules\AcademicRulesEngine;
use App\Services\Documents\DocumentService;
use App\Services\Notifications\NotificationService;
use App\Support\AdmissionAge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        private NotificationService $notifications,
        private DocumentService $documentService
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
        $allowLate = (bool) SystemSetting::getValue('allow_late_applications', false);
        $intakes = Intake::where('is_active', true)
            ->when(! $allowLate, fn ($q) => $q->where('application_closes', '>=', now()->toDateString()))
            ->orderBy('application_closes')
            ->get();
        $campuses = \App\Models\Campus::where('is_active', true)->orderBy('name')->get();

        $kcseYears = AdmissionAge::kcseYearOptions();
        $counties = self::KENYAN_COUNTIES;
        $profile = Auth::user()->studentProfile;
        $missingDocuments = $profile->getMissingRequiredDocuments();
        $optionalDocuments = $profile->getOptionalDocumentRequirements();

        $minAge = (int) config('ocrs.admission.min_age_years', 16);
        $maxAge = AdmissionAge::maximumAgeYears();
        $dobMax = AdmissionAge::youngestAllowedDob($minAge)->toDateString();
        $dobMin = AdmissionAge::oldestAllowedDob($maxAge)->toDateString();
        $kcseIndexMin = (int) config('ocrs.admission.kcse_index_min_digits', 10);
        $kcseIndexMax = (int) config('ocrs.admission.kcse_index_max_digits', 12);
        $degreeMinAge = (int) config('ocrs.admission.min_age_degree_years', 17);
        $adultAgeThreshold = $profile->adultAgeThreshold();
        $identityDocumentCode = $profile->requiredIdentityDocumentCode();

        return view('student.applications.create', compact(
            'programmes',
            'intakes',
            'campuses',
            'kcseYears',
            'counties',
            'missingDocuments',
            'optionalDocuments',
            'profile',
            'dobMin',
            'dobMax',
            'minAge',
            'degreeMinAge',
            'maxAge',
            'kcseIndexMin',
            'kcseIndexMax',
            'adultAgeThreshold',
            'identityDocumentCode'
        ));
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('action') === 'draft';
        $profile = Auth::user()->studentProfile;
        $indexMin = (int) config('ocrs.admission.kcse_index_min_digits', 10);
        $indexMax = (int) config('ocrs.admission.kcse_index_max_digits', 12);
        $minAgeFloor = (int) config('ocrs.admission.min_age_years', 16);
        $maxAge = AdmissionAge::maximumAgeYears();

        $rules = [
            'programme_id' => ['required', 'exists:programmes,id'],
            'intake_id' => ['required', 'exists:intakes,id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'kcse_mean_grade' => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:1', 'max:12'],
            'kcse_index_number' => [
                $isDraft ? 'nullable' : 'required',
                'string',
                "regex:/^\d{{$indexMin},{$indexMax}}$/",
            ],
            'kcse_year' => [
                $isDraft ? 'nullable' : 'required',
                'integer',
                Rule::in(AdmissionAge::kcseYearOptions()),
            ],
            'national_id' => array_values(array_filter([
                $isDraft ? 'nullable' : 'required',
                'string',
                'max:30',
                $isDraft && blank($request->input('national_id')) ? null : new KenyanIdentityNumber,
            ])),
            'county' => [$isDraft ? 'nullable' : 'required', Rule::in(self::KENYAN_COUNTIES)],
            'date_of_birth' => [
                $isDraft ? 'nullable' : 'required',
                'date',
                'before:today',
                'after_or_equal:'.AdmissionAge::oldestAllowedDob($maxAge)->toDateString(),
                'before_or_equal:'.AdmissionAge::youngestAllowedDob($minAgeFloor)->toDateString(),
            ],
            'gender' => [$isDraft ? 'nullable' : 'required', 'string', 'in:Male,Female,Other'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:20', new KenyanPhoneNumber],
            'employment_details' => ['nullable', 'string', 'max:500'],
        ];

        $missingDocuments = $profile->getMissingRequiredDocuments();
        foreach ($missingDocuments as $doc) {
            $rules["documents.{$doc->code}"] = ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'];
        }

        $optionalDocuments = $profile->getOptionalDocumentRequirements();
        foreach ($optionalDocuments as $doc) {
            $rules["documents.{$doc->code}"] = ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'];
        }

        // Allow age-appropriate identity upload even when DOB is first provided on this submit
        foreach (['national_id', 'birth_certificate'] as $identityCode) {
            $rules["documents.{$identityCode}"] = ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'];
        }

        $messages = [
            'kcse_index_number.regex' => "KCSE index number must be {$indexMin}–{$indexMax} digits (KNEC index is normally 11 digits).",
            'kcse_mean_grade.min' => 'KCSE mean grade points must be between 1 (E) and 12 (A).',
            'kcse_mean_grade.max' => 'KCSE mean grade points must be between 1 (E) and 12 (A).',
            'date_of_birth.required' => 'Date of birth is required to confirm eligibility for higher education.',
            'date_of_birth.before_or_equal' => "Applicants must be at least {$minAgeFloor} years old.",
            'date_of_birth.after_or_equal' => "Please confirm date of birth. Maximum supported applicant age is {$maxAge} years.",
            'gender.required' => 'Select your gender as it appears on your identification document.',
        ];

        $data = $request->validate($rules, $messages);

        $this->assertIntakeOpen($data['intake_id'], $isDraft);

        $programme = Programme::findOrFail($data['programme_id']);

        if (! empty($data['date_of_birth'])) {
            AdmissionAge::assertEligible(
                $data['date_of_birth'],
                $programme,
                isset($data['kcse_year']) ? (int) $data['kcse_year'] : null
            );

            if (! $isDraft && AdmissionAge::requiresGuardian($data['date_of_birth'])) {
                if (blank($data['next_of_kin_name'] ?? null) || blank($data['next_of_kin_phone'] ?? null)) {
                    throw ValidationException::withMessages([
                        'next_of_kin_name' => 'Applicants under 18 must provide a parent or guardian name and phone number.',
                        'next_of_kin_phone' => 'Applicants under 18 must provide a parent or guardian phone number.',
                    ]);
                }
            }
        }

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

        $profile->refresh();
        $this->assertUploadedIdentityDocumentsMatchAge($request, $profile);

        if ($isDraft) {
            $application = Application::create([
                'reference' => 'APP-'.strtoupper(Str::random(8)),
                'student_profile_id' => $profile->id,
                'programme_id' => $data['programme_id'],
                'intake_id' => $data['intake_id'],
                'campus_id' => $data['campus_id'] ?? null,
                'status' => ApplicationStatus::Draft,
            ]);

            $this->storeUploadedDocuments($request, $profile, $application->id);

            return redirect()->route('student.applications.index')
                ->with('success', 'Application saved as draft. You can continue uploading documents and pay the fee when ready.');
        }

        $eligibility = $this->rulesEngine->checkKcseEligibility($profile->fresh(), $programme);

        if (! $eligibility['eligible']) {
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
            $this->storeUploadedDocuments($request, $profile, $application->id);
            $this->notifications->notifyApplicationStatus(Auth::user(), $application->status->label(), $application->reference);

            return redirect()->route('student.applications.index')->with('error', $eligibility['message']);
        }

        $appFee = $this->resolveApplicationFee($programme, (int) $data['intake_id']);
        $hasPaid = $appFee && Payment::where('student_profile_id', $profile->id)
            ->where('fee_structure_id', $appFee->id)
            ->where('status', \App\Enums\PaymentStatus::Completed)
            ->exists();

        $finalStatus = ($appFee && ! $hasPaid) ? ApplicationStatus::PendingFee : ApplicationStatus::Submitted;

        $application = Application::create([
            'reference' => 'APP-'.strtoupper(Str::random(8)),
            'student_profile_id' => $profile->id,
            'programme_id' => $data['programme_id'],
            'intake_id' => $data['intake_id'],
            'campus_id' => $data['campus_id'] ?? null,
            'status' => $finalStatus,
            'submitted_at' => now(),
        ]);

        $this->storeUploadedDocuments($request, $profile, $application->id);

        \App\Models\ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'status' => $finalStatus->value,
            'notes' => $finalStatus === ApplicationStatus::PendingFee
                ? 'Application created; awaiting application fee payment.'
                : 'Application submitted for admissions review.',
            'user_id' => Auth::id(),
        ]);

        if ($finalStatus === ApplicationStatus::PendingFee) {
            return redirect()->route('student.payments.index')
                ->with('warning', 'Application saved. Pay the application fee to send it for review — you can keep uploading documents in parallel.');
        }

        $this->notifications->notifyApplicationStatus(
            Auth::user(),
            $application->status->label(),
            $application->reference
        );

        return redirect()->route('student.applications.index')
            ->with('success', 'Application submitted successfully. Admissions will review it shortly.');
    }

    public function cancel(Application $application)
    {
        if ($application->student_profile_id !== Auth::user()->studentProfile->id) {
            abort(403, 'Unauthorized action.');
        }

        if (! in_array($application->status, [ApplicationStatus::Draft, ApplicationStatus::PendingFee], true)) {
            return back()->with('error', 'You can only cancel draft or pending-fee applications.');
        }

        $application->update(['status' => ApplicationStatus::Cancelled]);

        \App\Models\ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'status' => ApplicationStatus::Cancelled->value,
            'notes' => 'Cancelled by student.',
            'user_id' => Auth::id(),
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

        if (! $letter || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($letter->letter_path)) {
            if (empty($application->studentProfile->admission_number)) {
                $application->studentProfile->update([
                    'admission_number' => config('ocrs.institution_code', 'OCRS').'-'.str_pad($application->id, 5, '0', STR_PAD_LEFT),
                ]);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.admission_letter', compact('application'));
            $path = 'letters/admission_'.$application->reference.'.pdf';
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());

            if (! $letter) {
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

    private function storeUploadedDocuments(Request $request, $profile, int $applicationId): void
    {
        if (! $request->hasFile('documents')) {
            return;
        }

        foreach ($request->file('documents') as $code => $file) {
            if ($file && $profile->canUploadDocumentType($code)) {
                $this->documentService->upload($profile, $file, $code, $applicationId, Auth::user());
            }
        }
    }

    private function assertUploadedIdentityDocumentsMatchAge(Request $request, $profile): void
    {
        if (! $request->hasFile('documents')) {
            return;
        }

        foreach ($request->file('documents') as $code => $file) {
            if (! $file) {
                continue;
            }

            if ($error = $profile->identityDocumentUploadError($code)) {
                throw ValidationException::withMessages([
                    "documents.{$code}" => $error,
                ]);
            }
        }
    }

    private function resolveApplicationFee(Programme $programme, int $intakeId): ?FeeStructure
    {
        $appFee = FeeStructure::where('programme_id', $programme->id)
            ->where('intake_id', $intakeId)
            ->where('fee_type', 'application')
            ->where('is_mandatory', true)
            ->first();

        if (! $appFee) {
            $appFee = FeeStructure::whereNull('programme_id')
                ->where('award_level', $programme->award_level)
                ->where('intake_id', $intakeId)
                ->where('fee_type', 'application')
                ->where('is_mandatory', true)
                ->first();
        }

        if (! $appFee) {
            $appFee = FeeStructure::whereNull('programme_id')
                ->whereNull('award_level')
                ->where('intake_id', $intakeId)
                ->where('fee_type', 'application')
                ->where('is_mandatory', true)
                ->first();
        }

        return $appFee;
    }

    private function assertIntakeOpen(int $intakeId, bool $isDraft): void
    {
        $intake = Intake::findOrFail($intakeId);
        $allowLate = (bool) SystemSetting::getValue('allow_late_applications', false);

        if (! $intake->is_active) {
            throw ValidationException::withMessages([
                'intake_id' => 'This intake is not accepting applications.',
            ]);
        }

        if (! $isDraft && ! $allowLate && $intake->application_closes && $intake->application_closes->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'intake_id' => 'The application window for this intake has closed.',
            ]);
        }
    }
}

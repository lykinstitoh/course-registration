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
use App\Services\AcademicRules\AcademicRulesEngine;
use App\Services\Documents\DocumentService;
use App\Services\Notifications\NotificationService;
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

        $latestKcseYear = now()->month >= 11 ? now()->year : now()->year - 1;
        $kcseYears = range($latestKcseYear, 1989);
        $counties = self::KENYAN_COUNTIES;
        $profile = Auth::user()->studentProfile;
        $missingDocuments = $profile->getMissingRequiredDocuments();
        $optionalDocuments = \App\Models\DocumentRequirement::where('is_required', false)->get()
            ->filter(fn ($req) => ! $profile->documents()
                ->where('document_type', $req->code)
                ->whereIn('status', [\App\Enums\DocumentStatus::Pending, \App\Enums\DocumentStatus::Verified])
                ->exists());

        return view('student.applications.create', compact(
            'programmes', 'intakes', 'campuses', 'kcseYears', 'counties', 'missingDocuments', 'optionalDocuments', 'profile'
        ));
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('action') === 'draft';
        $profile = Auth::user()->studentProfile;

        // Drafts still need programme + intake (schema FKs); other fields stay soft
        $rules = [
            'programme_id' => ['required', 'exists:programmes,id'],
            'intake_id' => ['required', 'exists:intakes,id'],
            'campus_id' => ['nullable', 'exists:campuses,id'],
            'kcse_mean_grade' => [$isDraft ? 'nullable' : 'required', 'numeric', 'min:1', 'max:12'],
            'kcse_index_number' => [$isDraft ? 'nullable' : 'required', 'string', 'max:30', 'regex:/^\d+$/'],
            'kcse_year' => [$isDraft ? 'nullable' : 'required', 'integer', Rule::in(range(now()->month >= 11 ? now()->year : now()->year - 1, 1989))],
            // National ID digits, or birth-certificate / passport style identifiers (Kenyan practice)
            'national_id' => [$isDraft ? 'nullable' : 'required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\\/\\-]+$/'],
            'county' => [$isDraft ? 'nullable' : 'required', Rule::in(self::KENYAN_COUNTIES)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:20'],
            'employment_details' => ['nullable', 'string'],
        ];

        $missingDocuments = $profile->getMissingRequiredDocuments();
        foreach ($missingDocuments as $doc) {
            // Non-blocking: documents encouraged on submit, but never block draft or fee payment path
            $rules["documents.{$doc->code}"] = ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'];
        }

        $optionalDocuments = \App\Models\DocumentRequirement::where('is_required', false)->pluck('code');
        foreach ($optionalDocuments as $code) {
            $rules["documents.{$code}"] = ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'];
        }

        $data = $request->validate($rules);

        $this->assertIntakeOpen($data['intake_id'], $isDraft);

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
                'programme_id' => $data['programme_id'],
                'intake_id' => $data['intake_id'],
                'campus_id' => $data['campus_id'] ?? null,
                'status' => ApplicationStatus::Draft,
            ]);

            $this->storeUploadedDocuments($request, $profile, $application->id);

            return redirect()->route('student.applications.index')
                ->with('success', 'Application saved as draft. You can continue uploading documents and pay the fee when ready.');
        }

        $programme = Programme::findOrFail($data['programme_id']);
        $eligibility = $this->rulesEngine->checkKcseEligibility($profile, $programme);

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
            if ($file) {
                $this->documentService->upload($profile, $file, $code, $applicationId, Auth::user());
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

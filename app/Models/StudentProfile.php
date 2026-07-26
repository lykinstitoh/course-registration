<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'admission_number',
        'national_id',
        'kcse_mean_grade',
        'kcse_index_number',
        'kcse_year',
        'date_of_birth',
        'gender',
        'county',
        'address',
        'next_of_kin_name',
        'next_of_kin_phone',
        'consent_data_processing',
        'consent_given_at',
        'employment_details',
    ];

    protected function casts(): array
    {
        return [
            'kcse_mean_grade' => 'decimal:2',
            'date_of_birth' => 'date',
            'consent_data_processing' => 'boolean',
            'consent_given_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public const IDENTITY_DOCUMENT_CODES = ['national_id', 'birth_certificate'];

    public function adultAgeThreshold(): int
    {
        return (int) config('ocrs.admission.guardian_required_under_years', 18);
    }

    /**
     * True when DOB is known and applicant is under the adult ID threshold (National ID age).
     */
    public function isUnderAdultIdentityAge(): bool
    {
        if (! $this->date_of_birth) {
            return false;
        }

        return $this->date_of_birth->age < $this->adultAgeThreshold();
    }

    /**
     * Age-based identity document: birth certificate under 18, National ID at 18+.
     * Null when date of birth is not yet on the profile.
     */
    public function requiredIdentityDocumentCode(): ?string
    {
        if (! $this->date_of_birth) {
            return null;
        }

        return $this->isUnderAdultIdentityAge() ? 'birth_certificate' : 'national_id';
    }

    public function resolveIdentityRequirement(): ?DocumentRequirement
    {
        $code = $this->requiredIdentityDocumentCode();
        if (! $code) {
            return null;
        }

        $requirement = DocumentRequirement::where('code', $code)->first();
        if (! $requirement) {
            return null;
        }

        // Present as mandatory for enrollment even if seeded optional (birth certificate).
        $requirement->is_required = true;
        if ($code === 'birth_certificate') {
            $requirement->name = 'Birth Certificate (required under '.$this->adultAgeThreshold().')';
        } elseif ($code === 'national_id') {
            $requirement->name = 'National ID / Passport (required at '.$this->adultAgeThreshold().'+)';
        }

        return $requirement;
    }

    /**
     * Mandatory checklist documents for this student (age-adjusted identity).
     */
    public function getRequiredDocumentRequirements()
    {
        $requirements = DocumentRequirement::where('is_required', true)
            ->whereNotIn('code', self::IDENTITY_DOCUMENT_CODES)
            ->orderBy('name')
            ->get();

        if ($identity = $this->resolveIdentityRequirement()) {
            $requirements->push($identity);
        }

        return $requirements->values();
    }

    /**
     * Optional uploads excluding the alternate identity document for the student's age.
     */
    public function getOptionalDocumentRequirements()
    {
        $identityCode = $this->requiredIdentityDocumentCode();
        $exclude = self::IDENTITY_DOCUMENT_CODES;

        return DocumentRequirement::where('is_required', false)
            ->whereNotIn('code', $exclude)
            ->orderBy('name')
            ->get()
            ->filter(fn (DocumentRequirement $req) => ! $this->documents()
                ->where('document_type', $req->code)
                ->whereIn('status', [\App\Enums\DocumentStatus::Pending, \App\Enums\DocumentStatus::Verified])
                ->exists())
            ->values();
    }

    public function getMissingRequiredDocuments()
    {
        $missing = collect();

        foreach ($this->getRequiredDocumentRequirements() as $req) {
            $hasDoc = $this->documents()
                ->where('document_type', $req->code)
                ->whereIn('status', [\App\Enums\DocumentStatus::Pending, \App\Enums\DocumentStatus::Verified])
                ->exists();

            if (! $hasDoc) {
                $missing->push($req);
            }
        }

        return $missing->values();
    }

    public function hasAllRequiredDocumentsVerified(): bool
    {
        if (! $this->date_of_birth) {
            return false;
        }

        foreach ($this->getRequiredDocumentRequirements() as $req) {
            $verified = $this->documents()
                ->where('document_type', $req->code)
                ->where('status', \App\Enums\DocumentStatus::Verified)
                ->exists();

            if (! $verified) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether this document type may be uploaded given the student's age.
     */
    public function canUploadDocumentType(string $code): bool
    {
        if (! in_array($code, self::IDENTITY_DOCUMENT_CODES, true)) {
            return DocumentRequirement::where('code', $code)->exists();
        }

        $expected = $this->requiredIdentityDocumentCode();

        return $expected !== null && $code === $expected;
    }

    public function identityDocumentUploadError(string $code): ?string
    {
        if (! in_array($code, self::IDENTITY_DOCUMENT_CODES, true)) {
            return null;
        }

        if (! $this->date_of_birth) {
            return 'Save your date of birth on the application form first so we can determine whether you need a National ID or a Birth Certificate.';
        }

        $expected = $this->requiredIdentityDocumentCode();
        $threshold = $this->adultAgeThreshold();

        if ($code === $expected) {
            return null;
        }

        if ($expected === 'birth_certificate') {
            return "You are under {$threshold}. Upload a Birth Certificate instead of a National ID.";
        }

        return "You are {$threshold} or older. Upload a National ID / Passport instead of a Birth Certificate.";
    }

    /**
     * Next actionable step for the Kenyan direct-admission journey (non-blocking parallel checklist).
     */
    public function getWorkflowNextStep(): array
    {
        $application = $this->applications()->latest()->first();

        if (! $application) {
            return [
                'code' => 'apply',
                'title' => 'Start your application',
                'message' => 'Select a programme, upload supporting documents, and submit for the current intake.',
                'route' => 'student.applications.create',
                'cta' => 'Apply now',
            ];
        }

        return match ($application->status) {
            \App\Enums\ApplicationStatus::Draft => [
                'code' => 'complete_draft',
                'title' => 'Complete your draft application',
                'message' => 'Finish your details and submit. You can pay the application fee and upload documents without waiting on admissions.',
                'route' => 'student.applications.create',
                'cta' => 'Continue application',
            ],
            \App\Enums\ApplicationStatus::PendingFee => [
                'code' => 'pay_application_fee',
                'title' => 'Pay application fee',
                'message' => 'Pay via M-Pesa or bank transfer. Document uploads can continue in parallel.',
                'route' => 'student.payments.index',
                'cta' => 'Pay fee',
            ],
            \App\Enums\ApplicationStatus::Submitted, \App\Enums\ApplicationStatus::UnderReview => [
                'code' => 'await_review',
                'title' => 'Application under review',
                'message' => 'Admissions is reviewing your file. You may still upload or replace documents while you wait.',
                'route' => 'student.documents.index',
                'cta' => 'Manage documents',
            ],
            \App\Enums\ApplicationStatus::MoreInfoRequired => [
                'code' => 'more_info',
                'title' => 'More information required',
                'message' => $application->rejection_reason
                    ? 'Admissions requested: '.$application->rejection_reason
                    : 'Please update your documents or profile details, then await further review.',
                'route' => 'student.documents.index',
                'cta' => 'Update documents',
            ],
            \App\Enums\ApplicationStatus::Waitlisted => [
                'code' => 'waitlisted',
                'title' => 'You are on the waitlist',
                'message' => 'We will notify you if a place opens. Keep your documents and contacts up to date.',
                'route' => 'student.applications.index',
                'cta' => 'View application',
            ],
            \App\Enums\ApplicationStatus::Rejected => [
                'code' => 'rejected',
                'title' => 'Application not successful',
                'message' => $application->rejection_reason ?? 'You may apply to another eligible programme in an open intake.',
                'route' => 'student.applications.create',
                'cta' => 'Apply to another programme',
            ],
            \App\Enums\ApplicationStatus::Approved => $this->getPostAdmissionNextStep($application),
            default => [
                'code' => 'applications',
                'title' => 'View your applications',
                'message' => 'Track status and next actions from your applications list.',
                'route' => 'student.applications.index',
                'cta' => 'View applications',
            ],
        };
    }

    private function getPostAdmissionNextStep($application): array
    {
        $docsOk = $this->hasAllRequiredDocumentsVerified();
        $minPercentage = \App\Models\SystemSetting::getValue('min_tuition_percentage', 50);
        $requiredAmount = $this->getRequiredTuitionAmount() * ($minPercentage / 100);
        $feesOk = $this->getPaidTuitionAmount() >= $requiredAmount;

        if (! $docsOk || ! $feesOk) {
            return [
                'code' => 'enrollment',
                'title' => 'Complete enrollment (documents + tuition)',
                'message' => 'Document verification and tuition deposit run in parallel. Finish both to unlock course registration.',
                'route' => 'student.enrollment.index',
                'cta' => 'Open enrollment checklist',
            ];
        }

        $confirmed = $this->registrations()
            ->where('status', \App\Enums\RegistrationStatus::Confirmed)
            ->exists();

        if (! $confirmed) {
            return [
                'code' => 'register_units',
                'title' => 'Register course units',
                'message' => 'Select your semester units. Registration confirms immediately after validation.',
                'route' => 'student.registrations.index',
                'cta' => 'Register units',
            ];
        }

        return [
            'code' => 'complete',
            'title' => 'You are fully registered',
            'message' => 'Download your admission letter, view your timetable, and track results when published.',
            'route' => 'student.timetable',
            'cta' => 'View timetable',
            'secondary_route' => 'student.applications.letter',
            'secondary_param' => $application,
            'secondary_cta' => 'Admission letter',
        ];
    }

    public function getRequiredTuitionAmount(): float
    {
        $application = $this->applications()->where('status', 'approved')->first();
        if (! $application) {
            return 0;
        }

        $tuitionFee = \App\Models\FeeStructure::where('programme_id', $application->programme_id)
            ->where('intake_id', $application->intake_id)
            ->where('fee_type', 'tuition')
            ->first();

        if (! $tuitionFee) {
            $tuitionFee = \App\Models\FeeStructure::whereNull('programme_id')
                ->where('award_level', $application->programme->award_level)
                ->where('intake_id', $application->intake_id)
                ->where('fee_type', 'tuition')
                ->first();
        }

        if (! $tuitionFee) {
            $tuitionFee = \App\Models\FeeStructure::whereNull('programme_id')
                ->whereNull('award_level')
                ->where('intake_id', $application->intake_id)
                ->where('fee_type', 'tuition')
                ->first();
        }

        if (! $tuitionFee) {
            return 0;
        }

        return (float) $tuitionFee->amount;
    }

    public function getPaidTuitionAmount(): float
    {
        return $this->payments()
            ->whereHas('feeStructure', function ($q) {
                $q->where('fee_type', 'tuition');
            })
            ->where('status', \App\Enums\PaymentStatus::Completed)
            ->sum('amount');
    }

    public function isEnrolled(): bool
    {
        if (! $this->applications()->where('status', \App\Enums\ApplicationStatus::Approved)->exists()) {
            return false;
        }

        if (! $this->hasAllRequiredDocumentsVerified()) {
            return false;
        }

        $minPercentage = \App\Models\SystemSetting::getValue('min_tuition_percentage', 50);
        $requiredAmount = $this->getRequiredTuitionAmount() * ($minPercentage / 100);

        return $this->getPaidTuitionAmount() >= $requiredAmount;
    }

    public function getCurrentSemesterLevel(): int
    {
        $latestRegistration = $this->registrations()
            ->with(['semester', 'items.courseUnit'])
            ->whereIn('status', [\App\Enums\RegistrationStatus::Submitted, \App\Enums\RegistrationStatus::Confirmed])
            ->latest('submitted_at')
            ->first();

        if (!$latestRegistration || $latestRegistration->items->isEmpty()) {
            return 1;
        }

        $maxLevel = $latestRegistration->items->max(function ($item) {
            return $item->courseUnit->semester_level;
        });

        // If the academic semester for their latest registration has lapsed, they move to the next level
        if ($latestRegistration->semester->ends_on < now()) {
            return $maxLevel + 1;
        }

        return $maxLevel;
    }
}

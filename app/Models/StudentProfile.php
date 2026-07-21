<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
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

    public function hasAllRequiredDocumentsVerified(): bool
    {
        $requiredDocs = \App\Models\DocumentRequirement::where('is_required', true)->get();
        
        foreach ($requiredDocs as $req) {
            $doc = $this->documents()
                ->where('document_type', $req->code)
                ->where('status', \App\Enums\DocumentStatus::Verified)
                ->first();
            
            if (!$doc) {
                return false;
            }
        }
        
        return true;
    }

    public function getRequiredTuitionAmount(): float
    {
        $application = $this->applications()->where('status', 'approved')->first();
        if (!$application) return 0;

        $tuitionFee = \App\Models\FeeStructure::where('programme_id', $application->programme_id)
            ->where('intake_id', $application->intake_id)
            ->where('fee_type', 'tuition')
            ->first();

        if (!$tuitionFee) {
            $tuitionFee = \App\Models\FeeStructure::whereNull('programme_id')
                ->where('award_level', $application->programme->award_level)
                ->where('intake_id', $application->intake_id)
                ->where('fee_type', 'tuition')
                ->first();
        }

        if (!$tuitionFee) return 0;

        return $tuitionFee->amount;
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
        if (!$this->hasAllRequiredDocumentsVerified()) {
            return false;
        }

        $minPercentage = \App\Models\SystemSetting::getValue('min_tuition_percentage', 100);
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

<?php

namespace App\Support;

use App\Models\Programme;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AdmissionAge
{
    public static function latestKcseYear(?Carbon $at = null): int
    {
        $at ??= now();

        // KCSE results are typically released around November; until then the latest cohort is prior year.
        return $at->month >= 11 ? $at->year : $at->year - 1;
    }

    public static function firstKcseYear(): int
    {
        return (int) config('ocrs.admission.kcse_first_year', 1989);
    }

    public static function kcseYearOptions(?Carbon $at = null): array
    {
        return range(self::latestKcseYear($at), self::firstKcseYear());
    }

    public static function minimumAgeForProgramme(?Programme $programme): int
    {
        $defaultMin = (int) config('ocrs.admission.min_age_years', 16);
        $degreeMin = (int) config('ocrs.admission.min_age_degree_years', 17);

        if ($programme && strtolower((string) $programme->award_level) === 'degree') {
            return $degreeMin;
        }

        return $defaultMin;
    }

    public static function maximumAgeYears(): int
    {
        return (int) config('ocrs.admission.max_age_years', 70);
    }

    /**
     * Youngest allowed DOB (most recent date) for a given minimum age.
     */
    public static function youngestAllowedDob(int $minAgeYears, ?Carbon $at = null): Carbon
    {
        return ($at ?? now())->copy()->subYears($minAgeYears)->startOfDay();
    }

    /**
     * Oldest allowed DOB (earliest date) for a given maximum age.
     */
    public static function oldestAllowedDob(int $maxAgeYears, ?Carbon $at = null): Carbon
    {
        return ($at ?? now())->copy()->subYears($maxAgeYears)->startOfDay();
    }

    public static function assertEligible(
        string $dateOfBirth,
        ?Programme $programme,
        ?int $kcseYear = null
    ): void {
        $dob = Carbon::parse($dateOfBirth)->startOfDay();
        $today = now()->startOfDay();

        if ($dob->greaterThanOrEqualTo($today)) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Date of birth must be before today.',
            ]);
        }

        $minAge = self::minimumAgeForProgramme($programme);
        $maxAge = self::maximumAgeYears();
        $age = $dob->age;

        if ($age < $minAge) {
            $label = $programme && strtolower((string) $programme->award_level) === 'degree'
                ? "degree programmes (minimum age {$minAge})"
                : "higher education programmes (minimum age {$minAge})";

            throw ValidationException::withMessages([
                'date_of_birth' => "Applicants must be at least {$minAge} years old for {$label}.",
            ]);
        }

        if ($age > $maxAge) {
            throw ValidationException::withMessages([
                'date_of_birth' => "Please confirm date of birth. Maximum supported applicant age is {$maxAge} years.",
            ]);
        }

        if ($kcseYear !== null) {
            $ageAtKcse = $kcseYear - $dob->year;
            $minAtKcse = (int) config('ocrs.admission.min_age_at_kcse', 14);
            $maxAtKcse = (int) config('ocrs.admission.max_age_at_kcse', 30);

            // Approximate calendar-year check: sitting KCSE far outside typical secondary-school ages
            if ($ageAtKcse < $minAtKcse || $ageAtKcse > $maxAtKcse) {
                throw ValidationException::withMessages([
                    'kcse_year' => "KCSE year ({$kcseYear}) is inconsistent with date of birth. Typical age when sitting KCSE is {$minAtKcse}–{$maxAtKcse}.",
                ]);
            }
        }
    }

    public static function requiresGuardian(string $dateOfBirth): bool
    {
        $threshold = (int) config('ocrs.admission.guardian_required_under_years', 18);

        return Carbon::parse($dateOfBirth)->age < $threshold;
    }
}

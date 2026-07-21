<?php

namespace App\Services\AcademicRules;

use App\Models\CourseUnit;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class AcademicRulesEngine
{
    public function checkKcseEligibility(StudentProfile $student, Programme $programme): array
    {
        if ($student->kcse_mean_grade === null) {
            return ['eligible' => false, 'message' => 'KCSE mean grade has not been provided.'];
        }

        if ((float) $student->kcse_mean_grade < (float) $programme->minimum_kcse_grade) {
            return [
                'eligible' => false,
                'message' => "KCSE mean grade ({$student->kcse_mean_grade}) does not meet the programme minimum ({$programme->minimum_kcse_grade}).",
            ];
        }

        return ['eligible' => true, 'message' => 'KCSE eligibility requirement met.'];
    }

    public function checkPrerequisites(StudentProfile $student, CourseUnit $courseUnit, array $selectedUnitIds = []): array
    {
        $prerequisites = $courseUnit->prerequisites;

        if ($prerequisites->isEmpty()) {
            return ['eligible' => true, 'message' => 'No prerequisites required.'];
        }

        $completedUnitIds = $student->results()
            ->where('status', 'published')
            ->whereNotNull('grade')
            ->pluck('course_unit_id');

        $eligibleUnitIds = $completedUnitIds->merge($selectedUnitIds)->unique();

        $missing = $prerequisites->filter(
            fn (CourseUnit $prereq) => ! $eligibleUnitIds->contains($prereq->id)
        );

        if ($missing->isNotEmpty()) {
            $codes = $missing->pluck('code')->join(', ');

            return [
                'eligible' => false,
                'message' => "Missing prerequisite course unit(s): {$codes}.",
            ];
        }

        return ['eligible' => true, 'message' => 'All prerequisites satisfied.'];
    }

    public function checkCapacity(CourseUnit $courseUnit, Semester $semester): array
    {
        $enrolled = $courseUnit->enrolledCount($semester->id);

        if ($enrolled >= $courseUnit->capacity) {
            return [
                'eligible' => false,
                'message' => "Course unit {$courseUnit->code} has reached capacity ({$courseUnit->capacity} seats).",
            ];
        }

        $remaining = $courseUnit->capacity - $enrolled;

        return [
            'eligible' => true,
            'message' => "{$remaining} seat(s) remaining.",
            'seats_remaining' => $remaining,
        ];
    }

    public function checkRegistrationDeadline(Semester $semester): array
    {
        if (now()->startOfDay()->gt($semester->registration_deadline)) {
            return [
                'eligible' => false,
                'message' => "Registration deadline ({$semester->registration_deadline->format('d M Y')}) has passed.",
            ];
        }

        return ['eligible' => true, 'message' => 'Within registration period.'];
    }

    public function validateRegistration(
        StudentProfile $student,
        CourseUnit $courseUnit,
        Semester $semester,
        ?Programme $programme = null,
        array $selectedUnitIds = []
    ): array {
        $checks = [];

        $deadlineCheck = $this->checkRegistrationDeadline($semester);
        $checks[] = $deadlineCheck;
        if (! $deadlineCheck['eligible']) {
            return $this->compileResult(collect($checks));
        }

        $capacityCheck = $this->checkCapacity($courseUnit, $semester);
        $checks[] = $capacityCheck;
        if (! $capacityCheck['eligible']) {
            return $this->compileResult(collect($checks));
        }

        $prereqCheck = $this->checkPrerequisites($student, $courseUnit, $selectedUnitIds);
        $checks[] = $prereqCheck;
        if (! $prereqCheck['eligible']) {
            return $this->compileResult(collect($checks));
        }

        if ($programme) {
            $kcseCheck = $this->checkKcseEligibility($student, $programme);
            $checks[] = $kcseCheck;
            if (! $kcseCheck['eligible']) {
                return $this->compileResult(collect($checks));
            }
        }

        return $this->compileResult(collect($checks));
    }

    private function compileResult(\Illuminate\Support\Collection $checks): array
    {
        $checks = collect($checks);
        $failed = $checks->first(fn ($c) => ! $c['eligible']);

        return [
            'eligible' => $failed === null,
            'message' => $failed['message'] ?? 'All academic rules passed.',
            'checks' => $checks->values()->all(),
        ];
    }
}

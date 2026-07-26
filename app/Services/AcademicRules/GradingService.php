<?php

namespace App\Services\AcademicRules;

use App\Models\Result;
use Illuminate\Support\Collection;

class GradingService
{
    /**
     * Common Kenyan university percentage → letter / grade-point scale (4.0).
     */
    public function gradeFromMarks(float $marks): array
    {
        $marks = max(0, min(100, $marks));

        return match (true) {
            $marks >= 70 => ['grade' => 'A', 'points' => 4.0, 'remark' => 'Excellent'],
            $marks >= 60 => ['grade' => 'B', 'points' => 3.0, 'remark' => 'Good'],
            $marks >= 50 => ['grade' => 'C', 'points' => 2.0, 'remark' => 'Satisfactory'],
            $marks >= 40 => ['grade' => 'D', 'points' => 1.0, 'remark' => 'Pass'],
            default => ['grade' => 'F', 'points' => 0.0, 'remark' => 'Fail'],
        };
    }

    public function pointsForGrade(?string $grade): float
    {
        return match (strtoupper((string) $grade)) {
            'A' => 4.0,
            'B' => 3.0,
            'C' => 2.0,
            'D' => 1.0,
            default => 0.0,
        };
    }

    public function isPass(?string $grade): bool
    {
        return in_array(strtoupper((string) $grade), ['A', 'B', 'C', 'D'], true);
    }

    /**
     * @param  Collection<int, Result>  $results
     */
    public function computeGpa(Collection $results): array
    {
        $qualityPoints = 0.0;
        $creditHours = 0;

        foreach ($results as $result) {
            $credits = (int) ($result->courseUnit->credit_units ?? 0);
            if ($credits < 1 || blank($result->grade)) {
                continue;
            }

            $qualityPoints += $this->pointsForGrade($result->grade) * $credits;
            $creditHours += $credits;
        }

        return [
            'gpa' => $creditHours > 0 ? round($qualityPoints / $creditHours, 2) : null,
            'credits' => $creditHours,
            'quality_points' => round($qualityPoints, 2),
        ];
    }
}

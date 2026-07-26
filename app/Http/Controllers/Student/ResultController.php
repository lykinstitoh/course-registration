<?php

namespace App\Http\Controllers\Student;

use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Services\AcademicRules\GradingService;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    public function __construct(private GradingService $grading) {}

    public function index()
    {
        $profile = Auth::user()->studentProfile;
        if (! $profile) {
            return redirect()->route('student.dashboard')->with('error', 'Complete your profile first.');
        }

        $results = $profile->results()
            ->with(['courseUnit', 'semester.intake'])
            ->where('status', ResultStatus::Published)
            ->get()
            ->sortBy([
                fn ($r) => $r->semester->starts_on?->timestamp ?? 0,
                fn ($r) => $r->courseUnit->code ?? '',
            ])
            ->values();

        $grouped = $results->groupBy('semester_id');
        $semesterSummaries = [];
        foreach ($grouped as $semesterId => $semesterResults) {
            $semesterSummaries[$semesterId] = $this->grading->computeGpa($semesterResults);
        }

        $overall = $this->grading->computeGpa($results);

        return view('student.results', compact('results', 'grouped', 'semesterSummaries', 'overall'));
    }
}

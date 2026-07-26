<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Semester;
use App\Services\AcademicRules\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    public function __construct(private GradingService $grading) {}

    public function index(Request $request)
    {
        $semesters = Semester::with('intake')->orderByDesc('starts_on')->get();
        $semesterId = (int) $request->input('semester_id', $semesters->firstWhere('is_active', true)?->id ?? $semesters->first()?->id);
        $semester = $semesters->firstWhere('id', $semesterId);
        $statusFilter = $request->input('status');

        $results = collect();
        if ($semester) {
            $query = Result::with(['studentProfile.user', 'courseUnit', 'semester'])
                ->where('semester_id', $semester->id)
                ->latest();

            if (in_array($statusFilter, ['pending', 'published'], true)) {
                $query->where('status', $statusFilter);
            }

            $results = $query->paginate(30)->withQueryString();
        }

        return view('admin.results.index', compact('semesters', 'semester', 'results', 'statusFilter'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
        ]);

        $semesterId = (int) $data['semester_id'];
        $created = 0;

        $registrations = Registration::with('items')
            ->where('semester_id', $semesterId)
            ->where('status', RegistrationStatus::Confirmed)
            ->get();

        foreach ($registrations as $registration) {
            foreach ($registration->items as $item) {
                $result = Result::firstOrCreate(
                    [
                        'student_profile_id' => $registration->student_profile_id,
                        'course_unit_id' => $item->course_unit_id,
                        'semester_id' => $semesterId,
                    ],
                    [
                        'status' => ResultStatus::Pending,
                        'marks' => null,
                        'grade' => null,
                    ]
                );

                if ($result->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return redirect()
            ->route('admin.results.index', ['semester_id' => $semesterId])
            ->with('success', "Generated {$created} pending result record(s) from confirmed registrations.");
    }

    public function edit(Result $result)
    {
        $result->load(['studentProfile.user', 'courseUnit', 'semester.intake']);

        return view('admin.results.edit', compact('result'));
    }

    public function update(Request $request, Result $result)
    {
        $data = $request->validate([
            'marks' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', 'in:pending,published'],
        ]);

        $graded = $this->grading->gradeFromMarks((float) $data['marks']);

        $result->update([
            'marks' => $data['marks'],
            'grade' => $graded['grade'],
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('admin.results.index', ['semester_id' => $result->semester_id])
            ->with('success', "Result updated for {$result->courseUnit->code} ({$graded['grade']}).");
    }

    public function publish(Request $request)
    {
        $data = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'result_ids' => ['nullable', 'array'],
            'result_ids.*' => ['exists:results,id'],
        ]);

        $query = Result::where('semester_id', $data['semester_id'])
            ->where('status', ResultStatus::Pending)
            ->whereNotNull('marks')
            ->whereNotNull('grade');

        if (! empty($data['result_ids'])) {
            $query->whereIn('id', $data['result_ids']);
        }

        $count = $query->update(['status' => ResultStatus::Published]);

        return back()->with('success', "Published {$count} result(s).");
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'marks' => ['required', 'array'],
            'marks.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $updated = 0;

        DB::transaction(function () use ($data, &$updated) {
            foreach ($data['marks'] as $resultId => $marks) {
                if ($marks === null || $marks === '') {
                    continue;
                }

                $result = Result::where('id', $resultId)
                    ->where('semester_id', $data['semester_id'])
                    ->first();

                if (! $result) {
                    continue;
                }

                $graded = $this->grading->gradeFromMarks((float) $marks);
                $result->update([
                    'marks' => $marks,
                    'grade' => $graded['grade'],
                ]);
                $updated++;
            }
        });

        return back()->with('success', "Saved marks for {$updated} result(s). Publish when ready.");
    }
}

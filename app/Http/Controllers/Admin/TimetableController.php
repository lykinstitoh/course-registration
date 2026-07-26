<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseUnit;
use App\Models\Semester;
use App\Models\TimetableEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $semesters = Semester::with('intake')->orderByDesc('starts_on')->get();
        $semesterId = (int) $request->input('semester_id', $semesters->firstWhere('is_active', true)?->id ?? $semesters->first()?->id);
        $semester = $semesters->firstWhere('id', $semesterId);

        $entries = collect();
        if ($semester) {
            $entries = TimetableEntry::with('courseUnit')
                ->where('semester_id', $semester->id)
                ->get()
                ->sortBy([
                    fn ($entry) => $entry->dayOrder(),
                    fn ($entry) => (string) $entry->starts_at,
                ])
                ->values();
        }

        return view('admin.timetable.index', compact('semesters', 'semester', 'entries'));
    }

    public function create(Request $request)
    {
        $semesters = Semester::with('intake')->orderByDesc('starts_on')->get();
        $units = CourseUnit::where('is_active', true)->orderBy('code')->get();
        $selectedSemesterId = $request->integer('semester_id') ?: $semesters->firstWhere('is_active', true)?->id;

        return view('admin.timetable.create', [
            'semesters' => $semesters,
            'units' => $units,
            'days' => TimetableEntry::DAYS,
            'selectedSemesterId' => $selectedSemesterId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($clash = $this->findClash($data)) {
            return back()->withInput()->with('error', $clash);
        }

        TimetableEntry::create($data);

        return redirect()
            ->route('admin.timetable.index', ['semester_id' => $data['semester_id']])
            ->with('success', 'Timetable slot created.');
    }

    public function edit(TimetableEntry $timetable)
    {
        $semesters = Semester::with('intake')->orderByDesc('starts_on')->get();
        $units = CourseUnit::where('is_active', true)->orderBy('code')->get();

        return view('admin.timetable.edit', [
            'entry' => $timetable,
            'semesters' => $semesters,
            'units' => $units,
            'days' => TimetableEntry::DAYS,
        ]);
    }

    public function update(Request $request, TimetableEntry $timetable)
    {
        $data = $this->validated($request);

        if ($clash = $this->findClash($data, $timetable->id)) {
            return back()->withInput()->with('error', $clash);
        }

        $timetable->update($data);

        return redirect()
            ->route('admin.timetable.index', ['semester_id' => $data['semester_id']])
            ->with('success', 'Timetable slot updated.');
    }

    public function destroy(TimetableEntry $timetable)
    {
        $semesterId = $timetable->semester_id;
        $timetable->delete();

        return redirect()
            ->route('admin.timetable.index', ['semester_id' => $semesterId])
            ->with('success', 'Timetable slot deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'course_unit_id' => ['required', 'exists:course_units,id'],
            'day_of_week' => ['required', Rule::in(TimetableEntry::DAYS)],
            'starts_at' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'ends_at' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'venue' => ['required', 'string', 'max:100'],
            'lecturer' => ['nullable', 'string', 'max:150'],
        ]);

        $data['starts_at'] = strlen($data['starts_at']) === 5 ? $data['starts_at'].':00' : $data['starts_at'];
        $data['ends_at'] = strlen($data['ends_at']) === 5 ? $data['ends_at'].':00' : $data['ends_at'];

        if ($data['ends_at'] <= $data['starts_at']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ends_at' => 'The end time must be after the start time.',
            ]);
        }

        return $data;
    }

    private function findClash(array $data, ?int $ignoreId = null): ?string
    {
        $query = TimetableEntry::with('courseUnit')
            ->where('semester_id', $data['semester_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($q) use ($data) {
                $q->where('starts_at', '<', $data['ends_at'])
                    ->where('ends_at', '>', $data['starts_at']);
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        // Same venue overlap
        $venueClash = (clone $query)->where('venue', $data['venue'])->first();
        if ($venueClash) {
            return "Venue {$data['venue']} already booked for {$venueClash->courseUnit->code} ({$venueClash->timeRange()}).";
        }

        // Same unit overlap
        $unitClash = (clone $query)->where('course_unit_id', $data['course_unit_id'])->first();
        if ($unitClash) {
            return "{$unitClash->courseUnit->code} already has a class overlapping this time ({$unitClash->timeRange()}).";
        }

        return null;
    }
}

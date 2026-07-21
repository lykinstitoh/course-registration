<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseUnit;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseUnitController extends Controller
{
    public function index()
    {
        $programmes = Programme::with(['courseUnits' => function($q) {
            $q->orderBy('semester_level')->orderBy('code');
        }])->where('is_active', true)->orderBy('name')->get();

        $unassignedUnits = CourseUnit::doesntHave('programmes')
            ->orderBy('semester_level')
            ->orderBy('code')
            ->get();

        return view('admin.course-units.index', compact('programmes', 'unassignedUnits'));
    }

    public function create()
    {
        $programmes = Programme::where('is_active', true)->orderBy('name')->get();
        return view('admin.course-units.create', compact('programmes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:course_units,code'],
            'name' => ['required', 'string', 'max:255'],
            'credit_units' => ['required', 'integer', 'min:1'],
            'capacity' => ['required', 'integer', 'min:1'],
            'semester_level' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'programme_ids' => ['nullable', 'array'],
            'programme_ids.*' => ['exists:programmes,id'],
        ]);

        $courseUnit = CourseUnit::create($data);

        if (isset($data['programme_ids'])) {
            $courseUnit->programmes()->sync($data['programme_ids']);
        }

        return redirect()->route('admin.course-units.index')->with('success', 'Course unit created successfully.');
    }

    public function edit(CourseUnit $courseUnit)
    {
        $programmes = Programme::where('is_active', true)->orderBy('name')->get();
        return view('admin.course-units.edit', compact('courseUnit', 'programmes'));
    }

    public function update(Request $request, CourseUnit $courseUnit)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('course_units')->ignore($courseUnit->id)],
            'name' => ['required', 'string', 'max:255'],
            'credit_units' => ['required', 'integer', 'min:1'],
            'capacity' => ['required', 'integer', 'min:1'],
            'semester_level' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'programme_ids' => ['nullable', 'array'],
            'programme_ids.*' => ['exists:programmes,id'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $courseUnit->update($data);

        if (isset($data['programme_ids'])) {
            $courseUnit->programmes()->sync($data['programme_ids']);
        } else {
            $courseUnit->programmes()->sync([]);
        }

        return redirect()->route('admin.course-units.index')->with('success', 'Course unit updated successfully.');
    }

    public function destroy(CourseUnit $courseUnit)
    {
        if ($courseUnit->registrationItems()->exists()) {
            return back()->with('error', 'Cannot delete a course unit that has active student registrations.');
        }

        $courseUnit->delete();

        return back()->with('success', 'Course unit deleted successfully.');
    }
}

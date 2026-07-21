<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseUnit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseUnitController extends Controller
{
    public function index()
    {
        $courseUnits = CourseUnit::orderBy('semester_level')
            ->orderBy('code')
            ->get()
            ->groupBy('semester_level');

        return view('admin.course-units.index', compact('courseUnits'));
    }

    public function create()
    {
        return view('admin.course-units.create');
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
        ]);

        CourseUnit::create($data);

        return redirect()->route('admin.course-units.index')->with('success', 'Course unit created successfully.');
    }

    public function edit(CourseUnit $courseUnit)
    {
        return view('admin.course-units.edit', compact('courseUnit'));
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
        ]);

        $data['is_active'] = $request->has('is_active');

        $courseUnit->update($data);

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

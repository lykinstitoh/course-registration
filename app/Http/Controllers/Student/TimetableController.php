<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TimetableEntry;
use Illuminate\Support\Facades\Auth;

class TimetableController extends Controller
{
    public function index()
    {
        $profile = Auth::user()->studentProfile;
        $registration = $profile->registrations()->where('status', 'confirmed')->latest()->first();

        $entries = collect();
        if ($registration) {
            $unitIds = $registration->items()->pluck('course_unit_id');
            $entries = TimetableEntry::with('courseUnit')
                ->where('semester_id', $registration->semester_id)
                ->whereIn('course_unit_id', $unitIds)
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get();
        }

        return view('student.timetable', compact('entries', 'registration'));
    }
}

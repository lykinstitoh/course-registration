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
        if (!$profile) {
            return redirect()->route('student.dashboard')->with('error', 'You must complete your profile and registration first.');
        }

        $registration = $profile->registrations()->where('status', 'confirmed')->latest()->first();
        
        if (!$registration) {
            return redirect()->route('student.dashboard')->with('error', 'You must have a confirmed course registration to view your timetable.');
        }

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

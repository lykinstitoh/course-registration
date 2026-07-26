<?php

namespace App\Http\Controllers\Student;

use App\Enums\RegistrationStatus;
use App\Enums\ResultStatus;
use App\Http\Controllers\Controller;
use App\Models\TimetableEntry;
use App\Services\AcademicRules\GradingService;
use Illuminate\Support\Facades\Auth;

class TimetableController extends Controller
{
    public function index()
    {
        $profile = Auth::user()->studentProfile;
        if (! $profile) {
            return redirect()->route('student.dashboard')->with('error', 'Complete your profile first.');
        }

        $registrations = $profile->registrations()
            ->with('semester.intake')
            ->where('status', RegistrationStatus::Confirmed)
            ->latest('confirmed_at')
            ->get();

        if ($registrations->isEmpty()) {
            return redirect()->route('student.registrations.index')
                ->with('warning', 'Register and confirm course units to view your timetable.');
        }

        $semesterId = (int) request('semester_id', $registrations->first()->semester_id);
        $registration = $registrations->firstWhere('semester_id', $semesterId) ?? $registrations->first();

        $unitIds = $registration->items()->pluck('course_unit_id');
        $entries = TimetableEntry::with('courseUnit')
            ->where('semester_id', $registration->semester_id)
            ->whereIn('course_unit_id', $unitIds)
            ->get()
            ->sortBy([
                fn ($entry) => $entry->dayOrder(),
                fn ($entry) => (string) $entry->starts_at,
            ])
            ->values();

        $byDay = $entries->groupBy('day_of_week');
        $days = TimetableEntry::DAYS;

        return view('student.timetable', compact('entries', 'byDay', 'days', 'registration', 'registrations'));
    }
}

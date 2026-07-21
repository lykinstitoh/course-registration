<?php

namespace App\Http\Controllers\Student;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\CourseUnit;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\RegistrationItem;
use App\Models\Semester;
use App\Services\AcademicRules\AcademicRulesEngine;
use App\Services\Payments\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    public function __construct(private AcademicRulesEngine $rulesEngine) {}

    public function index()
    {
        $profile = Auth::user()->studentProfile;
        if (!$profile) {
            return redirect()->route('student.dashboard')->with('error', 'You must complete your profile and application first.');
        }

        $application = $profile->applications()->where('status', 'approved')->first();
        if (!$application) {
            return redirect()->route('student.dashboard')->with('error', 'You must be admitted to an approved programme to register for courses.');
        }

        if (!$profile->isEnrolled()) {
            return redirect()->route('student.enrollment.index')->with('warning', 'You must complete your enrollment checklist before registering for courses.');
        }

        $registrations = $profile
            ->registrations()
            ->with(['semester.intake', 'items.courseUnit'])
            ->latest()
            ->get();

        $activeSemester = Semester::where('is_active', true)
            ->where('registration_deadline', '>=', now())
            ->first();

        $currentSemesterLevel = $profile->getCurrentSemesterLevel();

        $courseUnits = $activeSemester
            ? $application->programme->courseUnits()
                ->where('course_units.is_active', true)
                ->where('course_units.semester_level', $currentSemesterLevel)
                ->orderBy('course_units.code')
                ->get()
            : collect();

        return view('student.registrations.index', compact(
            'registrations',
            'activeSemester',
            'courseUnits',
            'currentSemesterLevel'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'course_unit_ids' => ['required', 'array', 'min:1'],
            'course_unit_ids.*' => ['exists:course_units,id'],
        ]);

        $profile = Auth::user()->studentProfile;
        $semester = Semester::findOrFail($data['semester_id']);
        $application = $profile->applications()->where('status', 'approved')->first();
        $programme = $application?->programme;

        if (!$profile->isEnrolled()) {
            return redirect()->route('student.enrollment.index')->with('warning', 'You must complete your enrollment checklist before registering for courses.');
        }

        $currentSemesterLevel = $profile->getCurrentSemesterLevel();
        
        $failed = [];
        foreach ($data['course_unit_ids'] as $unitId) {
            $unit = CourseUnit::find($unitId);
            
            if ($unit->semester_level !== $currentSemesterLevel) {
                $failed[] = "{$unit->code}: You can only register for courses in your current semester level (Level {$currentSemesterLevel}).";
                continue;
            }

            $result = $this->rulesEngine->validateRegistration(
                $profile,
                $unit,
                $semester,
                $programme,
                $data['course_unit_ids']
            );
            if (! $result['eligible']) {
                $failed[] = "{$unit->code}: {$result['message']}";
            }
        }

        if (! empty($failed)) {
            return back()->withErrors(['course_units' => implode(' | ', $failed)]);
        }

        $registration = Registration::create([
            'reference' => 'REG-'.strtoupper(Str::random(8)),
            'student_profile_id' => $profile->id,
            'semester_id' => $semester->id,
            'status' => RegistrationStatus::Submitted,
            'submitted_at' => now(),
        ]);

        foreach ($data['course_unit_ids'] as $unitId) {
            RegistrationItem::create([
                'registration_id' => $registration->id,
                'course_unit_id' => $unitId,
            ]);
        }

        return redirect()->route('student.registrations.index')
            ->with('success', "Registration {$registration->reference} submitted successfully.");
    }
}

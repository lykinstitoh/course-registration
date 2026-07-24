<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user->studentProfile;

        $application = $profile?->applications()->with(['programme', 'intake'])->latest()->first();
        $registration = $profile?->registrations()->latest()->first();
        $pendingPayments = $profile
            ? Payment::where('student_profile_id', $profile->id)->where('status', 'pending')->count()
            : 0;

        $activeIntake = Intake::where('is_active', true)
            ->where('application_closes', '>=', now())
            ->first();

        $nextStep = $profile?->getWorkflowNextStep();
        $isEnrolled = $profile?->isEnrolled() ?? false;

        return view('student.dashboard', compact(
            'user',
            'profile',
            'application',
            'registration',
            'pendingPayments',
            'activeIntake',
            'nextStep',
            'isEnrolled'
        ));
    }
}

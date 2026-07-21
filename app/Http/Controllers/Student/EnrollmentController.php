<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequirement;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    public function index()
    {
        $profile = Auth::user()->studentProfile;
        
        if (!$profile) {
            return redirect()->route('student.dashboard')->with('error', 'You must complete your profile first.');
        }

        $application = $profile->applications()->where('status', 'approved')->first();
        
        if (!$application) {
            return redirect()->route('student.dashboard')->with('error', 'You must have an approved application to enroll.');
        }

        $requiredDocs = DocumentRequirement::where('is_required', true)->get();
        $studentDocs = $profile->documents()->get()->keyBy('document_type');
        
        $documentsVerified = $profile->hasAllRequiredDocumentsVerified();

        $tuitionFee = $profile->getRequiredTuitionAmount();
        $minPercentage = SystemSetting::getValue('min_tuition_percentage', 100);
        $requiredDeposit = $tuitionFee * ($minPercentage / 100);
        $paidTuition = $profile->getPaidTuitionAmount();
        
        $feePaid = $paidTuition >= $requiredDeposit;

        $isEnrolled = $documentsVerified && $feePaid;

        return view('student.enrollment.index', compact(
            'application',
            'requiredDocs',
            'studentDocs',
            'documentsVerified',
            'tuitionFee',
            'minPercentage',
            'requiredDeposit',
            'paidTuition',
            'feePaid',
            'isEnrolled'
        ));
    }
}

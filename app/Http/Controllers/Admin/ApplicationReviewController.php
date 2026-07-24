<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationReviewController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index()
    {
        $applications = Application::with(['studentProfile.user', 'programme', 'intake', 'studentProfile.payments' => function($q) {
            $q->where('status', 'pending')->where('method', 'bank_transfer');
        }])
            ->latest()
            ->paginate(20);

        return view('admin.applications.index', compact('applications'));
    }

    public function show(Application $application)
    {
        $application->load([
            'studentProfile.user',
            'programme',
            'intake',
            'studentProfile.documents.requirement',
            'studentProfile.payments' => function ($query) {
                $query->where('status', \App\Enums\PaymentStatus::Pending)
                      ->where('method', 'bank_transfer')
                      ->whereHas('feeStructure', function ($q) {
                          $q->where('fee_type', 'application');
                      });
            }
        ]);

        return view('admin.applications.show', compact('application'));
    }

    public function review(Request $request, Application $application)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,waitlist,more_info'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $status = match ($data['action']) {
            'approve' => ApplicationStatus::Approved,
            'reject' => ApplicationStatus::Rejected,
            'waitlist' => ApplicationStatus::Waitlisted,
            'more_info' => ApplicationStatus::MoreInfoRequired,
        };

        $application->update([
            'status' => $status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        \App\Models\ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'status' => $status,
            'notes' => $request->input('notes') ?? $request->input('rejection_reason'),
            'user_id' => Auth::id(),
        ]);

        if ($status === ApplicationStatus::Approved) {
            $application->studentProfile->update([
                'admission_number' => config('ocrs.institution_code', 'OCRS').'-'.str_pad($application->id, 5, '0', STR_PAD_LEFT),
            ]);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.admission_letter', compact('application'));
            $path = 'letters/admission_'.$application->reference.'.pdf';
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $pdf->output());

            \App\Models\AdmissionLetter::create([
                'student_profile_id' => $application->student_profile_id,
                'application_id' => $application->id,
                'letter_path' => $path,
                'generated_at' => now(),
            ]);
        }

        $user = $application->studentProfile->user;
        $this->notifications->notifyApplicationStatus(
            $user,
            $status->label(),
            $application->reference
        );

        return back()->with('success', "Application {$application->reference} {$data['action']}d.");
    }
}

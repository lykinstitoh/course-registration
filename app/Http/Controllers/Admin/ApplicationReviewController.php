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
        $applications = Application::with(['studentProfile.user', 'programme', 'intake'])
            ->latest()
            ->paginate(20);

        return view('admin.applications.index', compact('applications'));
    }

    public function review(Request $request, Application $application)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string'],
        ]);

        $status = $data['action'] === 'approve'
            ? ApplicationStatus::Approved
            : ApplicationStatus::Rejected;

        $application->update([
            'status' => $status,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        if ($status === ApplicationStatus::Approved) {
            $application->studentProfile->update([
                'admission_number' => config('ocrs.institution_code').'-'.str_pad($application->id, 5, '0', STR_PAD_LEFT),
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

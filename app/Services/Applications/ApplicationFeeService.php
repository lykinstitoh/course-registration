<?php

namespace App\Services\Applications;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Payment;
use App\Services\Notifications\NotificationService;

class ApplicationFeeService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * Advance pending-fee applications after a completed application-fee payment.
     * Matches programme-specific, award-level, and universal fee structures.
     */
    public function processCompletedPayment(Payment $payment): void
    {
        $fee = $payment->feeStructure;
        if (! $fee || $fee->fee_type !== 'application') {
            return;
        }

        $query = Application::where('student_profile_id', $payment->student_profile_id)
            ->where('intake_id', $fee->intake_id)
            ->where('status', ApplicationStatus::PendingFee);

        if ($fee->programme_id) {
            $query->where('programme_id', $fee->programme_id);
        } elseif ($fee->award_level) {
            $query->whereHas('programme', fn ($q) => $q->where('award_level', $fee->award_level));
        }

        $applications = $query->get();

        foreach ($applications as $application) {
            $application->update([
                'status' => ApplicationStatus::Submitted,
                'submitted_at' => $application->submitted_at ?? now(),
            ]);

            if ($payment->studentProfile?->user) {
                $this->notifications->notifyApplicationStatus(
                    $payment->studentProfile->user,
                    $application->status->label(),
                    $application->reference
                );
            }
        }
    }
}

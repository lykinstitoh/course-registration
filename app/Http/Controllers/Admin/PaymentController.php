<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index()
    {
        $payments = Payment::with(['studentProfile.user', 'feeStructure'])
            ->latest()
            ->paginate(20);

        return view('admin.payments.index', compact('payments'));
    }

    public function review(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,reject'],
        ]);

        if ($payment->status !== PaymentStatus::Pending) {
            return back()->with('error', 'Only pending payments can be reviewed.');
        }

        if ($data['action'] === 'approve') {
            $payment->update([
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
            ]);

            if ($payment->studentProfile && $payment->studentProfile->user) {
                $this->notifications->notifyPaymentConfirmation(
                    $payment->studentProfile->user,
                    $payment->reference,
                    (string) $payment->amount,
                    $payment->bank_reference ?? 'N/A'
                );
            }

            $this->processApplicationFee($payment);

            return back()->with('success', "Payment {$payment->reference} approved successfully.");
        } else {
            $payment->update([
                'status' => PaymentStatus::Failed,
            ]);

            return back()->with('success', "Payment {$payment->reference} rejected.");
        }
    }

    private function processApplicationFee(Payment $payment): void
    {
        if ($payment->feeStructure && $payment->feeStructure->fee_type === 'application') {
            $application = \App\Models\Application::where('student_profile_id', $payment->student_profile_id)
                ->where('programme_id', $payment->feeStructure->programme_id)
                ->where('intake_id', $payment->feeStructure->intake_id)
                ->where('status', \App\Enums\ApplicationStatus::PendingFee)
                ->first();

            if ($application) {
                $application->update([
                    'status' => \App\Enums\ApplicationStatus::Submitted,
                    'submitted_at' => now(),
                ]);

                if ($payment->studentProfile && $payment->studentProfile->user) {
                    $this->notifications->notifyApplicationStatus(
                        $payment->studentProfile->user,
                        $application->status->label(),
                        $application->reference
                    );
                }
            }
        }
    }
}

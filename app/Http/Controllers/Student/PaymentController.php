<?php

namespace App\Http\Controllers\Student;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Services\Notifications\NotificationService;
use App\Services\Payments\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private MpesaService $mpesa,
        private NotificationService $notifications
    ) {}

    public function index()
    {
        $profile = Auth::user()->studentProfile;

        // Reconcile / expire stuck M-Pesa STK payments first.
        $processingPayments = $profile->payments()
            ->where('status', PaymentStatus::Processing)
            ->get();

        foreach ($processingPayments as $payment) {
            if (filled($payment->mpesa_checkout_request_id)) {
                $this->mpesa->queryStkStatus($payment);
            } else {
                $this->mpesa->cancelStalePayment($payment, 'Incomplete STK attempt cleared.');
            }
        }

        $payments = $profile->payments()->with('feeStructure')->latest()->get();

        $activeApps = $profile->applications()
            ->with('programme')
            ->whereIn('status', ['approved', 'pending_fee'])
            ->get();

        $fees = collect();

        foreach ($activeApps as $app) {
            $appFees = collect();
            if ($app->status === \App\Enums\ApplicationStatus::Approved) {
                $appFees = $this->getApplicableFees($app);
            } elseif ($app->status === \App\Enums\ApplicationStatus::PendingFee) {
                $appFees = $this->getApplicableFees($app, 'application');
            }

            foreach ($appFees as $fee) {
                $fee->application_context = $app->programme->name;

                if (! $fees->contains('id', $fee->id)) {
                    $fees->push($fee);
                }
            }
        }

        $activePaymentFeeIds = $payments->whereIn('status', [
            PaymentStatus::Completed,
            PaymentStatus::Pending,
            PaymentStatus::Processing,
        ])->pluck('fee_structure_id')->toArray();

        $fees = $fees->reject(fn ($fee) => in_array($fee->id, $activePaymentFeeIds));

        // Ensure bank details exist in settings
        if (! \App\Models\SystemSetting::where('key', 'bank_name')->exists()) {
            \App\Models\SystemSetting::insert([
                ['group' => 'payment', 'key' => 'bank_name', 'value' => 'Equity Bank', 'type' => 'string'],
                ['group' => 'payment', 'key' => 'bank_account_name', 'value' => 'OCRS University', 'type' => 'string'],
                ['group' => 'payment', 'key' => 'bank_account_number', 'value' => '0123456789', 'type' => 'string'],
                ['group' => 'payment', 'key' => 'bank_branch', 'value' => 'Nairobi CBD', 'type' => 'string'],
            ]);
        }

        $settings = \App\Models\SystemSetting::where('group', 'payment')->pluck('value', 'key');
        $activeMethods = collect();
        if ($settings->get('enable_mpesa') == '1') {
            $activeMethods->push((object) ['code' => 'mpesa', 'name' => 'M-Pesa']);
        }
        if ($settings->get('enable_bank_transfer') == '1') {
            $activeMethods->push((object) ['code' => 'bank_transfer', 'name' => 'Bank Transfer']);
        }

        $awaitingPaymentId = request('awaiting');

        return view('student.payments.index', compact('payments', 'fees', 'activeMethods', 'settings', 'awaitingPaymentId'));
    }

    public function initiate(Request $request)
    {
        $settings = \App\Models\SystemSetting::where('group', 'payment')->pluck('value', 'key');
        $activeMethods = [];
        if ($settings->get('enable_mpesa') == '1') $activeMethods[] = 'mpesa';
        if ($settings->get('enable_bank_transfer') == '1') $activeMethods[] = 'bank_transfer';

        $data = $request->validate([
            'fee_structure_id' => ['required', 'exists:fee_structures,id'],
            'method' => ['required', \Illuminate\Validation\Rule::in($activeMethods)],
            'phone' => ['required_if:method,mpesa', 'nullable', 'string', 'max:20'],
            'bank_reference' => ['required_if:method,bank_transfer', 'nullable', 'string', 'max:100', 'unique:payments,bank_reference'],
            'receipt' => ['required_if:method,bank_transfer', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $profile = Auth::user()->studentProfile;
        $fee = FeeStructure::whereKey($data['fee_structure_id'])->firstOrFail();

        // Resolve the application this fee belongs to (supports concurrent pending_fee + approved apps)
        $applications = $profile->applications()
            ->with('programme')
            ->whereIn('status', ['approved', 'pending_fee'])
            ->get();

        $matchedApp = null;
        $applicableFees = collect();
        foreach ($applications as $app) {
            $feeType = $app->status === \App\Enums\ApplicationStatus::PendingFee ? 'application' : null;
            $feesForApp = $this->getApplicableFees($app, $feeType);
            if ($feesForApp->contains('id', $fee->id)) {
                $matchedApp = $app;
                $applicableFees = $feesForApp;
                break;
            }
        }

        if (! $matchedApp) {
            abort(403, 'This fee structure does not apply to your application.');
        }

        // Prevent duplicate pending/completed payments for the same fee.
        // Auto-clear timed-out processing STK attempts so retries are not blocked forever.
        $blocking = Payment::where('student_profile_id', $profile->id)
            ->where('fee_structure_id', $fee->id)
            ->whereIn('status', [PaymentStatus::Completed, PaymentStatus::Pending, PaymentStatus::Processing])
            ->latest()
            ->first();

        if ($blocking) {
            if ($blocking->status === PaymentStatus::Completed) {
                return back()->with('error', 'This fee is already paid.');
            }

            if ($blocking->status === PaymentStatus::Processing) {
                $this->mpesa->queryStkStatus($blocking);
                $blocking->refresh();

                if ($blocking->status === PaymentStatus::Completed) {
                    return redirect()
                        ->route('student.payments.index')
                        ->with('success', 'Your previous M-Pesa payment was confirmed.');
                }

                if ($blocking->status === PaymentStatus::Processing) {
                    // Still within the STK window — ask student to finish or cancel.
                    if (! $this->mpesa->expireIfTimedOut($blocking, 90)) {
                        return redirect()
                            ->route('student.payments.index', ['awaiting' => $blocking->id])
                            ->with('warning', 'A payment is already in progress. Complete the M-Pesa prompt on your phone, or cancel it below and try again.');
                    }
                    $blocking->refresh();
                }
            }

            if ($blocking->status === PaymentStatus::Pending && $blocking->method === 'bank_transfer') {
                return back()->with('error', 'You already have a pending bank transfer for this fee awaiting finance review.');
            }

            // Timed-out / failed processing no longer blocks.
            if (in_array($blocking->status, [PaymentStatus::Completed, PaymentStatus::Pending], true)) {
                return back()->with('error', 'You already have an active or completed payment for this fee.');
            }
        }

        $payment = Payment::create([
            'reference' => 'PAY-'.strtoupper(Str::random(8)),
            'student_profile_id' => $profile->id,
            'fee_structure_id' => $fee->id,
            'amount' => $fee->amount,
            'method' => $data['method'],
            'bank_reference' => $data['bank_reference'] ?? null,
            'receipt_path' => $request->hasFile('receipt') ? $request->file('receipt')->store('receipts', 'public') : null,
            'status' => PaymentStatus::Pending,
        ]);

        if ($data['method'] === 'mpesa') {
            $result = $this->mpesa->initiateStkPush($payment, $data['phone']);

            if ($result['success']) {
                return redirect()
                    ->route('student.payments.index', ['awaiting' => $payment->id])
                    ->with('success', ($result['message'] ?? 'STK Push sent.').' Confirm on your phone — this page will refresh the status automatically.');
            }

            return back()->with('error', $result['message']);
        }

        return back()->with('success', "Bank transfer logged successfully with reference {$payment->bank_reference}. It is currently under review by our finance team.");
    }

    public function status(Request $request, Payment $payment)
    {
        $profile = Auth::user()->studentProfile;
        if ($payment->student_profile_id !== $profile->id) {
            abort(403);
        }

        $result = ['success' => true, 'status' => $payment->status->value, 'message' => 'Current status loaded.'];

        if ($payment->status === PaymentStatus::Processing && filled($payment->mpesa_checkout_request_id)) {
            $result = $this->mpesa->queryStkStatus($payment);
            $payment->refresh();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result['success'],
                'status' => $payment->status->value,
                'label' => $payment->status->label(),
                'message' => $result['message'],
                'receipt' => $payment->mpesa_receipt,
                'reference' => $payment->reference,
            ]);
        }

        return redirect()
            ->route('student.payments.index')
            ->with($result['success'] && $payment->status === PaymentStatus::Completed ? 'success' : 'warning', $result['message']);
    }

    public function cancel(Payment $payment)
    {
        $profile = Auth::user()->studentProfile;
        if ($payment->student_profile_id !== $profile->id) {
            abort(403);
        }

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
            return back()->with('error', 'Only pending or processing payments can be cancelled.');
        }

        // Bank transfers pending finance review should stay until finance acts,
        // unless the student explicitly cancels an unfinished M-Pesa attempt.
        if ($payment->status === PaymentStatus::Pending && $payment->method === 'bank_transfer') {
            return back()->with('error', 'Pending bank transfers are reviewed by finance. Contact support if you need help.');
        }

        // One last status check in case money already went through.
        if ($payment->status === PaymentStatus::Processing && filled($payment->mpesa_checkout_request_id)) {
            $check = $this->mpesa->queryStkStatus($payment);
            $payment->refresh();
            if ($payment->status === PaymentStatus::Completed) {
                return redirect()
                    ->route('student.payments.index')
                    ->with('success', $check['message'] ?? 'Payment was already completed.');
            }
        }

        $this->mpesa->cancelStalePayment($payment);

        return redirect()
            ->route('student.payments.index')
            ->with('success', 'Payment cancelled. You can pay this fee again now.');
    }

    private function getApplicableFees($app, $feeType = null)
    {
        $query = FeeStructure::where('intake_id', $app->intake_id)
            ->where(function ($q) use ($app) {
                $q->where('programme_id', $app->programme_id)
                  ->orWhere('award_level', $app->programme->award_level)
                  ->orWhere(function ($q2) {
                      $q2->whereNull('programme_id')->whereNull('award_level');
                  });
            })
            ->orderByDesc('created_at');

        if ($feeType) {
            $query->where('fee_type', $feeType);
        }

        $fees = $query->get();
        $finalFees = collect();
        foreach ($fees->groupBy('fee_type') as $type => $groupFees) {
            $specific = $groupFees->firstWhere('programme_id', $app->programme_id);
            if ($specific) {
                $finalFees->push($specific);
                continue;
            }
            $level = $groupFees->firstWhere('award_level', $app->programme->award_level);
            if ($level) {
                $finalFees->push($level);
                continue;
            }
            $universal = $groupFees->first(fn($f) => is_null($f->programme_id) && is_null($f->award_level));
            if ($universal) {
                $finalFees->push($universal);
            }
        }
        return $finalFees;
    }

    public function receipt(Payment $payment)
    {
        $profile = Auth::user()->studentProfile;
        
        if ($payment->student_profile_id !== $profile->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($payment->status !== PaymentStatus::Completed) {
            return back()->with('error', 'Receipt is only available for completed payments.');
        }

        $payment->load('feeStructure');
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('student.payments.receipt', compact('payment', 'profile'));
        
        return $pdf->download("Receipt_{$payment->reference}.pdf");
    }
}

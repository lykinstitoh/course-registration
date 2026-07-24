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
                // Attach the programme context so the UI can differentiate multiple applications
                $fee->application_context = $app->programme->name;
                
                // Avoid duplicating the exact same fee structure in the collection
                if (!$fees->contains('id', $fee->id)) {
                    $fees->push($fee);
                }
            }
        }

        // Prevent double payments: filter out fees that are already Completed or Pending
        $activePaymentFeeIds = $payments->whereIn('status', [\App\Enums\PaymentStatus::Completed, \App\Enums\PaymentStatus::Pending])
                                        ->pluck('fee_structure_id')
                                        ->toArray();
                                        
        $fees = $fees->reject(function ($fee) use ($activePaymentFeeIds) {
            return in_array($fee->id, $activePaymentFeeIds);
        });

        // Ensure bank details exist in settings
        if (!\App\Models\SystemSetting::where('key', 'bank_name')->exists()) {
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
            $activeMethods->push((object)['code' => 'mpesa', 'name' => 'M-Pesa']);
        }
        if ($settings->get('enable_bank_transfer') == '1') {
            $activeMethods->push((object)['code' => 'bank_transfer', 'name' => 'Bank Transfer']);
        }

        return view('student.payments.index', compact('payments', 'fees', 'activeMethods', 'settings'));
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

        // Prevent duplicate pending/completed payments for the same fee
        $alreadyActive = Payment::where('student_profile_id', $profile->id)
            ->where('fee_structure_id', $fee->id)
            ->whereIn('status', [PaymentStatus::Completed, PaymentStatus::Pending, PaymentStatus::Processing])
            ->exists();

        if ($alreadyActive) {
            return back()->with('error', 'You already have an active or completed payment for this fee.');
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

            return back()->with($result['success'] ? 'success' : 'error', $result['message']);
        }

        return back()->with('success', "Bank transfer logged successfully with reference {$payment->bank_reference}. It is currently under review by our finance team.");
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

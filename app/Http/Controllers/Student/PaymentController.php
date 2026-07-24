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

        $approvedApp = $profile->applications()->where('status', 'approved')->first();
        $pendingApp = $profile->applications()->where('status', 'pending_fee')->first();
        
        $fees = collect();

        if ($approvedApp) {
            $fees = $fees->merge($this->getApplicableFees($approvedApp));
        }

        if ($pendingApp) {
            $fees = $fees->merge($this->getApplicableFees($pendingApp, 'application'));
        }

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
        $application = $profile->applications()->whereIn('status', ['approved', 'pending_fee'])->firstOrFail();
        $fee = FeeStructure::whereKey($data['fee_structure_id'])->firstOrFail();
        
        // Ensure the fee is applicable to the student
        $applicableFees = $this->getApplicableFees($application);
        if (!$applicableFees->contains('id', $fee->id)) {
            abort(403, 'This fee structure does not apply to your application.');
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

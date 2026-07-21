<?php

namespace App\Http\Controllers\Student;

use App\Enums\PaymentMethod;
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

        $application = $profile->applications()->where('status', 'approved')->first();
        $fees = $application
            ? FeeStructure::where('programme_id', $application->programme_id)
                ->where('intake_id', $application->intake_id)
                ->get()
            : collect();

        return view('student.payments.index', compact('payments', 'fees'));
    }

    public function initiate(Request $request)
    {
        $data = $request->validate([
            'fee_structure_id' => ['required', 'exists:fee_structures,id'],
            'method' => ['required', 'in:mpesa_stk,bank_transfer'],
            'phone' => ['required_if:method,mpesa_stk', 'nullable', 'string', 'max:20'],
        ]);

        $profile = Auth::user()->studentProfile;
        $application = $profile->applications()->where('status', 'approved')->firstOrFail();
        $fee = FeeStructure::whereKey($data['fee_structure_id'])
            ->where('programme_id', $application->programme_id)
            ->where('intake_id', $application->intake_id)
            ->firstOrFail();

        $payment = Payment::create([
            'reference' => 'PAY-'.strtoupper(Str::random(8)),
            'student_profile_id' => $profile->id,
            'fee_structure_id' => $fee->id,
            'amount' => $fee->amount,
            'method' => PaymentMethod::from($data['method']),
            'status' => PaymentStatus::Pending,
        ]);

        if ($data['method'] === 'mpesa_stk') {
            $result = $this->mpesa->initiateStkPush($payment, $data['phone']);

            return back()->with($result['success'] ? 'success' : 'error', $result['message']);
        }

        return back()->with('success', "Bank transfer initiated. Use reference {$payment->reference} as payment description.");
    }
}

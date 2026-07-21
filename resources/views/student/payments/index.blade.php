@extends('layouts.ocrs')
@section('title', 'Payments')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'payments'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Fee Payments</h1>
        @if($fees->isNotEmpty())
            <div class="card">
                <h3>Pay Fees</h3>
                @foreach($fees as $fee)
                    <form method="POST" action="{{ route('student.payments.initiate') }}" style="border-bottom:1px solid var(--border);padding:1rem 0;">
                        @csrf
                        <input type="hidden" name="fee_structure_id" value="{{ $fee->id }}">
                        <p><strong>{{ $fee->description }}</strong> — KES {{ number_format($fee->amount) }}</p>
                        <div>
                            <select name="method" required onchange="togglePhone(this)" style="margin-bottom:.5rem;">
                                <option value="">-- Select Payment Method --</option>
                                @foreach($activeMethods as $method)
                                    <option value="{{ $method->code }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="phone" value="{{ auth()->user()->phone }}" placeholder="M-Pesa phone (07XX)" style="display:none;">
                            <input type="text" name="bank_reference" placeholder="Bank Slip Reference No." style="display:none;">
                        </div>
                        <div class="bank-details" style="display:none; margin-top:1rem; padding:1rem; background:var(--surface); border:1px solid var(--border); border-radius:4px;">
                            <h4 style="margin-bottom:.5rem;">Bank Account Details</h4>
                            <p style="font-size:0.9rem; margin-bottom:0.25rem;"><strong>Bank Name:</strong> {{ $settings->get('bank_name') }}</p>
                            <p style="font-size:0.9rem; margin-bottom:0.25rem;"><strong>Account Name:</strong> {{ $settings->get('bank_account_name') }}</p>
                            <p style="font-size:0.9rem; margin-bottom:0.25rem;"><strong>Account No:</strong> {{ $settings->get('bank_account_number') }}</p>
                            <p style="font-size:0.9rem; margin-bottom:0;"><strong>Branch:</strong> {{ $settings->get('bank_branch') }}</p>
                        </div>
                        <button class="btn btn-accent" type="submit" style="margin-top:.5rem;">Pay Now</button>
                    </form>
                @endforeach
            </div>
        @endif
        <div class="card">
            <h3>Payment History</h3>
            <table>
                <thead><tr><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Gateway Ref</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->reference }}</td>
                            <td>KES {{ number_format($payment->amount) }}</td>
                            <td>{{ str_replace('_', ' ', Str::title($payment->method)) }}</td>
                            <td>{{ $payment->status->label() }}</td>
                            <td>{{ $payment->mpesa_receipt ?? $payment->bank_reference ?? '—' }}</td>
                            <td>
                                @if($payment->status->value === 'completed')
                                    <a href="{{ route('student.payments.receipt', $payment) }}" class="btn btn-sm btn-outline" target="_blank">Download PDF</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function togglePhone(select) {
    const form = select.closest('form');
    const phoneInput = form.querySelector('input[name="phone"]');
    const bankInput = form.querySelector('input[name="bank_reference"]');
    const bankDetails = form.querySelector('.bank-details');
    
    phoneInput.style.display = 'none';
    phoneInput.required = false;

    bankInput.style.display = 'none';
    bankInput.required = false;
    bankInput.value = '';

    bankDetails.style.display = 'none';

    if (select.value === 'mpesa') {
        phoneInput.style.display = 'block';
        phoneInput.required = true;
    } else if (select.value === 'bank_transfer') {
        bankInput.style.display = 'block';
        bankInput.required = true;
        bankDetails.style.display = 'block';
    }
}
</script>
@endsection

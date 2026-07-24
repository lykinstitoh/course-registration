@extends('layouts.ocrs')
@section('title', 'Payments')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'payments'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Fee Payments</h1>
        @if(session('success'))
            <div class="alert" style="background:#dcfce7;color:#166534;padding:1rem;margin-bottom:1rem;border-radius:6px;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert" style="background:#fee2e2;color:#991b1b;padding:1rem;margin-bottom:1rem;border-radius:6px;">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert" style="background:#fff7ed;color:#9a3412;padding:1rem;margin-bottom:1rem;border-radius:6px;">{{ session('warning') }}</div>
        @endif

        @if($fees->isNotEmpty())
            <div class="card">
                <h3>Pay Fees</h3>
                @foreach($fees as $fee)
                    <form method="POST" action="{{ route('student.payments.initiate') }}" enctype="multipart/form-data" style="border-bottom:1px solid var(--border);padding:1rem 0;">
                        @csrf
                        <input type="hidden" name="fee_structure_id" value="{{ $fee->id }}">
                        <p>
                            <strong>{{ $fee->description }}</strong>
                            @if($fee->application_context) <span style="color:#6b7280; font-size:0.9em;">({{ $fee->application_context }})</span> @endif
                            — KES {{ number_format($fee->amount) }}
                        </p>
                        <div>
                            <select name="method" required onchange="togglePhone(this)" style="margin-bottom:.5rem;">
                                <option value="">-- Select Payment Method --</option>
                                @foreach($activeMethods as $method)
                                    <option value="{{ $method->code }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="phone" value="{{ auth()->user()->phone }}" placeholder="M-Pesa phone (07XX)" style="display:none; margin-top:.5rem;">
                            <input type="text" name="bank_reference" placeholder="Bank Slip Reference No." style="display:none; margin-top:.5rem;">
                            <div class="receipt-upload" style="display:none; margin-top:.5rem;">
                                <label style="display:block;font-size:0.9rem;margin-bottom:.25rem;">Attach Bank Receipt (PDF/Image)</label>
                                <input type="file" name="receipt" accept="image/*,.pdf" style="display:block;">
                            </div>
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
            <p id="payment-status-note" style="display:none;color:#92400e;margin-bottom:1rem;"></p>
            <table>
                <thead><tr><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Gateway Ref</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr data-payment-id="{{ $payment->id }}" data-payment-status="{{ $payment->status->value }}">
                            <td>{{ $payment->reference }}</td>
                            <td>KES {{ number_format($payment->amount) }}</td>
                            <td>{{ str_replace('_', ' ', \Illuminate\Support\Str::title($payment->method)) }}</td>
                            <td class="payment-status-label">{{ $payment->status->label() }}</td>
                            <td class="payment-receipt">{{ $payment->mpesa_receipt ?? $payment->bank_reference ?? '—' }}</td>
                            <td>
                                @if($payment->status->value === 'completed')
                                    <a href="{{ route('student.payments.receipt', $payment) }}" class="btn btn-sm btn-outline" target="_blank">Download PDF</a>
                                @elseif($payment->status->value === 'processing' && $payment->mpesa_checkout_request_id)
                                    <form method="POST" action="{{ route('student.payments.status', $payment) }}" style="display:inline;" class="status-check-form">
                                        @csrf
                                        <button class="btn btn-sm btn-outline" type="submit">Check status</button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No payments yet.</td></tr>
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
    const receiptUpload = form.querySelector('.receipt-upload');

    phoneInput.style.display = 'none';
    phoneInput.required = false;
    bankInput.style.display = 'none';
    bankInput.required = false;
    bankInput.value = '';
    receiptUpload.style.display = 'none';
    form.querySelector('input[name="receipt"]').required = false;
    bankDetails.style.display = 'none';

    if (select.value === 'mpesa') {
        phoneInput.style.display = 'block';
        phoneInput.required = true;
    } else if (select.value === 'bank_transfer') {
        bankInput.style.display = 'block';
        bankInput.required = true;
        receiptUpload.style.display = 'block';
        form.querySelector('input[name="receipt"]').required = true;
        bankDetails.style.display = 'block';
    }
}

(function pollProcessingPayments() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value;
    const note = document.getElementById('payment-status-note');
    const awaitingId = @json($awaitingPaymentId);
    const rows = Array.from(document.querySelectorAll('tr[data-payment-status="processing"]'));
    if (!rows.length || !csrf) {
        return;
    }

    note.style.display = 'block';
    note.textContent = 'Confirm the M-Pesa prompt on your phone. Checking payment status…';

    let attempts = 0;
    const maxAttempts = 12;

    async function checkRow(row) {
        const id = row.getAttribute('data-payment-id');
        const response = await fetch(@json(url('/student/payments')) + '/' + id + '/status', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!response.ok) {
            return null;
        }
        return response.json();
    }

    const timer = setInterval(async () => {
        attempts += 1;
        let completed = false;
        for (const row of rows) {
            if (row.getAttribute('data-payment-status') !== 'processing') {
                continue;
            }
            try {
                const data = await checkRow(row);
                if (!data) continue;
                row.querySelector('.payment-status-label').textContent = data.label;
                if (data.receipt) {
                    row.querySelector('.payment-receipt').textContent = data.receipt;
                }
                row.setAttribute('data-payment-status', data.status);
                if (data.status === 'completed') {
                    completed = true;
                    note.textContent = data.message || 'Payment confirmed.';
                    note.style.background = '#dcfce7';
                    note.style.color = '#166534';
                    note.style.padding = '0.75rem';
                    note.style.borderRadius = '6px';
                } else if (data.status === 'failed') {
                    note.textContent = data.message || 'Payment failed. You can try again.';
                    note.style.color = '#991b1b';
                } else if (data.message) {
                    note.textContent = data.message;
                }
            } catch (e) {
                // keep polling
            }
        }

        if (completed || attempts >= maxAttempts) {
            clearInterval(timer);
            if (completed) {
                setTimeout(() => window.location.href = @json(route('student.payments.index')), 1200);
            } else if (attempts >= maxAttempts) {
                note.textContent = 'Still waiting for M-Pesa. Use “Check status” or refresh this page in a moment.';
            }
        }
    }, 5000);

    if (awaitingId) {
        // kick first check sooner
        setTimeout(() => {
            const row = document.querySelector('tr[data-payment-id="' + awaitingId + '"]');
            if (row) checkRow(row);
        }, 2500);
    }
})();
</script>
@endsection

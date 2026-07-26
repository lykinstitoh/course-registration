@extends('layouts.ocrs')
@section('title', 'Confirm M-Pesa Payment')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
@php
    $status = $payment->status->value;
    $isProcessing = $status === 'processing';
    $isCompleted = $status === 'completed';
    $isFailed = in_array($status, ['failed', 'reversed'], true);
@endphp
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'payments'])
    <div>
        <div class="card" style="max-width:560px;margin:0 auto;">
            <div style="text-align:center;margin-bottom:1.25rem;">
                <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:{{ $isCompleted ? '#d5f5e3' : ($isFailed ? '#fadbd8' : 'rgba(240,180,41,.2)') }};color:{{ $isCompleted ? 'var(--success)' : ($isFailed ? 'var(--danger)' : 'var(--primary)') }};font-size:1.75rem;font-weight:700;margin-bottom:.75rem;">
                    @if($isCompleted)
                        ✓
                    @elseif($isFailed)
                        !
                    @else
                        M
                    @endif
                </div>
                <h2 style="margin-bottom:.35rem;" id="confirm-title">
                    @if($isCompleted)
                        Payment confirmed
                    @elseif($isFailed)
                        Payment not completed
                    @else
                        Confirm on your phone
                    @endif
                </h2>
                <p style="color:var(--muted);font-size:.95rem;" id="confirm-message">
                    @if($isCompleted)
                        Your M-Pesa payment was received successfully.
                    @elseif($isFailed)
                        The STK prompt was cancelled, timed out, or declined. You can try again from Payments.
                    @else
                        An M-Pesa prompt was sent to <strong>{{ $phone }}</strong>. Enter your PIN to authorize the payment.
                    @endif
                </p>
            </div>

            <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:1rem;margin-bottom:1.25rem;">
                <div style="display:flex;justify-content:space-between;gap:1rem;margin-bottom:.5rem;">
                    <span style="color:var(--muted);">Fee</span>
                    <strong>{{ $payment->feeStructure?->description ?? 'Fee payment' }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;gap:1rem;margin-bottom:.5rem;">
                    <span style="color:var(--muted);">Amount</span>
                    <strong>KES {{ number_format($payment->amount) }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;gap:1rem;margin-bottom:.5rem;">
                    <span style="color:var(--muted);">Reference</span>
                    <strong>{{ $payment->reference }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;gap:1rem;margin-bottom:.5rem;">
                    <span style="color:var(--muted);">Status</span>
                    <strong id="confirm-status-label">{{ $payment->status->label() }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;gap:1rem;">
                    <span style="color:var(--muted);">M-Pesa receipt</span>
                    <strong id="confirm-receipt">{{ $payment->mpesa_receipt ?: '—' }}</strong>
                </div>
            </div>

            @if($isProcessing)
            <ol style="color:var(--muted);font-size:.9rem;padding-left:1.25rem;margin-bottom:1.25rem;line-height:1.7;">
                <li>Unlock your phone and open the M-Pesa prompt.</li>
                <li>Enter your M-Pesa PIN and submit.</li>
                <li>This page updates automatically when Safaricom confirms the payment.</li>
            </ol>
            <p id="confirm-poll-note" style="text-align:center;color:#92400e;font-size:.9rem;margin-bottom:1rem;">
                Waiting for confirmation…
            </p>
            @endif

            <div id="confirm-actions" style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center;">
                @if($isProcessing)
                    <form method="POST" action="{{ route('student.payments.status', $payment) }}" id="manual-status-form">
                        @csrf
                        <button class="btn btn-outline" type="submit">Check status</button>
                    </form>
                    <form method="POST" action="{{ route('student.payments.cancel', $payment) }}" onsubmit="return confirm('Cancel this M-Pesa attempt so you can try again?');">
                        @csrf
                        <button class="btn btn-outline" type="submit" style="color:var(--danger);border-color:var(--danger);">Cancel &amp; retry</button>
                    </form>
                @elseif($isCompleted)
                    <a href="{{ route('student.payments.receipt', $payment) }}" class="btn btn-accent" target="_blank">Download receipt</a>
                    <a href="{{ route('student.payments.index') }}" class="btn btn-primary">Back to payments</a>
                @else
                    <a href="{{ route('student.payments.index') }}" class="btn btn-primary">Try again</a>
                @endif
                @if($isProcessing)
                    <a href="{{ route('student.payments.index') }}" class="btn btn-outline">Back to payments</a>
                @endif
            </div>
        </div>
    </div>
</div>

@if($isProcessing)
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const statusUrl = @json(route('student.payments.status', $payment));
    const paymentsUrl = @json(route('student.payments.index'));
    const titleEl = document.getElementById('confirm-title');
    const messageEl = document.getElementById('confirm-message');
    const statusEl = document.getElementById('confirm-status-label');
    const receiptEl = document.getElementById('confirm-receipt');
    const noteEl = document.getElementById('confirm-poll-note');
    const actionsEl = document.getElementById('confirm-actions');

    if (!csrf) return;

    let attempts = 0;
    const maxAttempts = 24; // ~2 minutes at 5s

    async function poll() {
        attempts += 1;
        try {
            const response = await fetch(statusUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (!response.ok) return;
            const data = await response.json();
            statusEl.textContent = data.label || data.status;
            if (data.receipt) {
                receiptEl.textContent = data.receipt;
            }
            if (data.message) {
                noteEl.textContent = data.message;
            }

            if (data.status === 'completed') {
                clearInterval(timer);
                titleEl.textContent = 'Payment confirmed';
                messageEl.textContent = data.message || 'Your M-Pesa payment was received successfully.';
                noteEl.style.display = 'none';
                actionsEl.innerHTML = ''
                    + '<a href="' + @json(route('student.payments.receipt', $payment)) + '" class="btn btn-accent" target="_blank">Download receipt</a>'
                    + '<a href="' + paymentsUrl + '" class="btn btn-primary">Back to payments</a>';
                setTimeout(function () {
                    window.location.href = data.redirect || paymentsUrl;
                }, 1500);
                return;
            }

            if (data.status === 'failed' || data.status === 'reversed') {
                clearInterval(timer);
                titleEl.textContent = 'Payment not completed';
                messageEl.textContent = data.message || 'The STK prompt was cancelled, timed out, or declined.';
                noteEl.style.display = 'none';
                actionsEl.innerHTML = '<a href="' + paymentsUrl + '" class="btn btn-primary">Try again</a>';
                return;
            }
        } catch (e) {
            // keep polling
        }

        if (attempts >= maxAttempts) {
            clearInterval(timer);
            noteEl.textContent = 'Still waiting for M-Pesa. Tap “Check status”, or cancel and retry if the phone prompt expired.';
        }
    }

    const timer = setInterval(poll, 5000);
    setTimeout(poll, 2000);
})();
</script>
@endif
@endsection

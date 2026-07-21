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
                        <div class="grid-2" style="margin-top:.5rem;">
                            <select name="method" required>
                                @foreach($activeMethods as $method)
                                    <option value="{{ $method->code }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="phone" placeholder="M-Pesa phone (07XX)">
                        </div>
                        <button class="btn btn-accent" type="submit" style="margin-top:.5rem;">Pay Now</button>
                    </form>
                @endforeach
            </div>
        @endif
        <div class="card">
            <h3>Payment History</h3>
            <table>
                <thead><tr><th>Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Receipt</th></tr></thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->reference }}</td>
                            <td>KES {{ number_format($payment->amount) }}</td>
                            <td>{{ str_replace('_', ' ', Str::title($payment->method)) }}</td>
                            <td>{{ $payment->status->label() }}</td>
                            <td>{{ $payment->mpesa_receipt ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

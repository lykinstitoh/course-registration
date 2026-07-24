@extends('layouts.ocrs')
@section('title', 'Review Payments')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'payments'])
    <div class="card">
        <h2>Payment Review</h2>
        <table>
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Student</th>
                    <th>Fee Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Bank Ref</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->reference }}</td>
                        <td>{{ $payment->studentProfile->user->name }}</td>
                        <td>{{ $payment->feeStructure ? ucfirst($payment->feeStructure->fee_type) : 'N/A' }}</td>
                        <td>{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                        <td>
                            @if($payment->method === 'bank_transfer')
                                Bank Transfer
                            @elseif($payment->method === 'mpesa')
                                M-Pesa
                            @else
                                {{ $payment->method }}
                            @endif
                        </td>
                        <td>{{ $payment->bank_reference ?? '—' }}</td>
                        <td>
                            @if($payment->status->value === 'completed')
                                <span class="badge" style="background:#dcfce7; color:#166534;">Completed</span>
                            @elseif($payment->status->value === 'pending')
                                <span class="badge" style="background:#fef3c7; color:#92400e;">Pending</span>
                            @else
                                <span class="badge badge-amber">{{ ucfirst($payment->status->value) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->status->value === 'pending' && $payment->method === 'bank_transfer')
                                <form method="POST" action="{{ route('admin.payments.review', $payment) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <button class="btn btn-primary btn-sm" type="submit" onclick="return confirm('Approve this bank transfer?');">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.payments.review', $payment) }}" style="display:inline;margin-left:.25rem;">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <button class="btn btn-outline btn-sm" style="color:var(--danger); border-color:var(--danger);" type="submit" onclick="return confirm('Reject this bank transfer?');">Reject</button>
                                </form>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $payments->links() }}
    </div>
</div>
@endsection

@extends('layouts.ocrs')
@section('title', 'Review Applications')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'applications'])
    <div class="card">
        <h2>Application Review</h2>
        <table>
            <thead><tr><th>Ref</th><th>Student</th><th>Programme</th><th>KCSE</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($applications as $app)
                    <tr>
                        <td>{{ $app->reference }}</td>
                        <td>{{ $app->studentProfile->user->name }}</td>
                        <td>{{ $app->programme->name }}</td>
                        <td>{{ $app->studentProfile->kcse_mean_grade ?? '—' }}</td>
                        <td>{{ $app->status->label() }}</td>
                        <td>
                            @if(in_array($app->status->value, ['submitted', 'under_review']))
                                <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-primary btn-sm">Review Details</a>
                            @elseif($app->status->value === 'pending_fee')
                                @php
                                    $hasBankTransfer = $app->studentProfile->payments->contains(function($p) {
                                        return $p->status === \App\Enums\PaymentStatus::Pending 
                                            && $p->method === 'bank_transfer' 
                                            && $p->feeStructure 
                                            && $p->feeStructure->fee_type === 'application';
                                    });
                                @endphp
                                @if($hasBankTransfer)
                                    <a href="{{ route('admin.applications.show', $app) }}" class="badge" style="background:#fef3c7; color:#92400e; text-decoration:none;">Pending Bank Transfer</a>
                                @else
                                    <a href="{{ route('admin.applications.show', $app) }}" style="color:#6b7280; font-size:0.875rem; text-decoration:none;">Awaiting Payment</a>
                                @endif
                            @else
                                <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-outline btn-sm">View</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $applications->links() }}
    </div>
</div>
@endsection

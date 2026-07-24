@extends('layouts.ocrs')
@section('title', 'Application Details')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'applications'])
    <div class="card" style="max-width: 800px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h2>Application Details ({{ $application->reference }})</h2>
            <a href="{{ route('admin.applications.index') }}" class="btn btn-outline">Back to List</a>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:2rem;">
            <div>
                <h4 style="margin-bottom:.5rem;">Applicant Bio</h4>
                <p><strong>Name:</strong> {{ $application->studentProfile->user->name }}</p>
                <p><strong>Phone:</strong> {{ $application->studentProfile->user->phone }}</p>
                <p><strong>Email:</strong> {{ $application->studentProfile->user->email }}</p>
                <p><strong>ID / Birth Cert No.:</strong> {{ $application->studentProfile->national_id }}</p>
                <p><strong>DOB:</strong> {{ $application->studentProfile->date_of_birth?->format('d M Y') ?? 'N/A' }}</p>
                <p><strong>Gender:</strong> {{ $application->studentProfile->gender ?? 'N/A' }}</p>
                <p><strong>County:</strong> {{ $application->studentProfile->county }}</p>
                <p><strong>Next of Kin:</strong> {{ $application->studentProfile->next_of_kin_name }} ({{ $application->studentProfile->next_of_kin_phone }})</p>
            </div>
            <div>
                <h4 style="margin-bottom:.5rem;">Academic Details</h4>
                <p><strong>Programme:</strong> {{ $application->programme->name }}</p>
                <p><strong>Intake:</strong> {{ $application->intake->name }}</p>
                <p><strong>Campus:</strong> {{ $application->campus?->name ?? 'N/A' }}</p>
                <p><strong>KCSE Index:</strong> {{ $application->studentProfile->kcse_index_number }}</p>
                <p><strong>KCSE Year:</strong> {{ $application->studentProfile->kcse_year }}</p>
                <p><strong>KCSE Grade:</strong> {{ $application->studentProfile->kcse_mean_grade }}</p>
                <p><strong>Current Status:</strong> <span class="badge badge-primary">{{ $application->status->label() }}</span></p>
            </div>
        </div>

        @if($application->studentProfile->documents->isNotEmpty())
        <div style="margin-bottom:2rem;">
            <h4 style="margin-bottom:.5rem;">Uploaded Documents</h4>
            <ul style="list-style:none; padding:0;">
                @foreach($application->studentProfile->documents as $doc)
                    <li style="margin-bottom:.5rem; padding:.5rem; background:var(--surface); border:1px solid var(--border); border-radius:4px; display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                        <span>
                            {{ $doc->displayName() }}
                            <span class="badge" style="margin-left:.5rem;">{{ $doc->status->label() }}</span>
                        </span>
                        <a href="{{ route('admin.documents.download', $doc) }}" target="_blank" style="color:var(--primary);">Download</a>
                    </li>
                @endforeach
            </ul>
        </div>
        @endif

        @php
            $pendingPayment = $application->studentProfile->payments->first();
        @endphp

        @if($pendingPayment)
        <div style="margin-bottom:2rem; padding:1rem; border:1px solid #f59e0b; background:#fffbeb; border-radius:8px;">
            <h4 style="color:#92400e; margin-bottom:.5rem;">Pending Application Fee Payment</h4>
            <p><strong>Bank Reference:</strong> {{ $pendingPayment->bank_reference }}</p>
            <p><strong>Amount:</strong> KES {{ number_format($pendingPayment->amount) }}</p>
            @if($pendingPayment->receipt_path)
                <p style="margin-top:.5rem;"><a href="{{ Storage::url($pendingPayment->receipt_path) }}" target="_blank" class="btn btn-outline btn-sm">View Attached Receipt</a></p>
            @else
                <p style="color:#b91c1c; font-size:0.875rem;">No receipt attached.</p>
            @endif

            <div style="margin-top:1rem; display:flex; gap:.5rem;">
                <form method="POST" action="{{ route('admin.payments.review', $pendingPayment) }}">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-primary" type="submit" onclick="return confirm('Approve this bank transfer? The application will be moved to Submitted.');">Approve Payment</button>
                </form>
                <form method="POST" action="{{ route('admin.payments.review', $pendingPayment) }}">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn-outline" style="color:var(--danger); border-color:var(--danger);" type="submit" onclick="return confirm('Reject this payment?');">Reject Payment</button>
                </form>
            </div>
        </div>
        @endif

        @if(in_array($application->status->value, ['submitted', 'under_review', 'more_info_required', 'waitlisted']))
        <div style="padding-top:1rem; border-top:1px solid var(--border); display:flex; gap:1rem; flex-wrap:wrap;">
            <form method="POST" action="{{ route('admin.applications.review', $application) }}">
                @csrf
                <input type="hidden" name="action" value="approve">
                <button class="btn btn-primary" type="submit" onclick="return confirm('Approve this application and issue an admission letter?');">Approve</button>
            </form>

            <form method="POST" action="{{ route('admin.applications.review', $application) }}" onsubmit="return handleAction(this, 'more information', true);">
                @csrf
                <input type="hidden" name="action" value="more_info">
                <input type="hidden" name="rejection_reason" class="reason-input">
                <button class="btn btn-outline" type="submit">Request More Info</button>
            </form>
            
            <form method="POST" action="{{ route('admin.applications.review', $application) }}" onsubmit="return handleAction(this, 'waitlist');">
                @csrf
                <input type="hidden" name="action" value="waitlist">
                <input type="hidden" name="notes" class="notes-input">
                <button class="btn btn-outline" style="color:#d97706; border-color:#d97706;" type="submit">Waitlist</button>
            </form>

            <form method="POST" action="{{ route('admin.applications.review', $application) }}" onsubmit="return handleAction(this, 'reject', true);">
                @csrf
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="rejection_reason" class="reason-input">
                <button class="btn btn-outline" style="color:var(--danger); border-color:var(--danger);" type="submit">Reject</button>
            </form>
        </div>
        @endif

    </div>
</div>
<script>
function handleAction(form, actionType, requireReason = false) {
    const reason = prompt(`Please enter the reason/notes for ${actionType}:`);
    if (requireReason && !reason) {
        return false;
    }
    if (actionType === 'reject' || actionType === 'more information') {
        form.querySelector('.reason-input').value = reason || '';
    } else {
        form.querySelector('.notes-input').value = reason || '';
    }
    return true;
}
</script>
@endsection

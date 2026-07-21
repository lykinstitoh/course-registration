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
                                <form method="POST" action="{{ route('admin.applications.review', $app) }}" style="display:inline;">@csrf<input type="hidden" name="action" value="approve"><button class="btn btn-primary" type="submit" onclick="return confirm('Approve this application?');">Approve</button></form>
                                <form method="POST" action="{{ route('admin.applications.review', $app) }}" style="display:inline;margin-left:.25rem;" onsubmit="return handleReject(this);">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="rejection_reason" class="reason-input">
                                    <button class="btn btn-outline" style="color:var(--danger); border-color:var(--danger);" type="submit">Reject</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $applications->links() }}
    </div>
</div>
<script>
function handleReject(form) {
    const reason = prompt("Please enter the reason for rejection:");
    if (!reason) {
        return false; // Cancel submission if no reason provided
    }
    form.querySelector('.reason-input').value = reason;
    return true;
}
</script>
@endsection

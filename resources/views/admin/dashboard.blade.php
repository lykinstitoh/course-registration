@extends('layouts.ocrs')
@section('title', 'Admin Dashboard')
@section('nav')<span style="font-size:.875rem;color:var(--muted);">{{ auth()->user()->role->label() }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'dashboard'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Administration Panel</h1>
        <div class="grid-3">
            <div class="card stat"><strong>{{ $stats['students'] }}</strong><span>Students</span></div>
            <div class="card stat"><strong>{{ $stats['pending_applications'] }}</strong><span>Pending Applications</span></div>
            <div class="card stat"><strong>{{ $stats['pending_documents'] }}</strong><span>Documents to Verify</span></div>
            <div class="card stat"><strong>KES {{ number_format($stats['payments_today']) }}</strong><span>Payments Today</span></div>
            <div class="card stat"><strong>{{ $stats['active_registrations'] }}</strong><span>Active Registrations</span></div>
        </div>
        <div class="card">
            <h3>Recent Applications</h3>
            <table>
                <thead><tr><th>Ref</th><th>Student</th><th>Programme</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($recentApplications as $app)
                        <tr>
                            <td>{{ $app->reference }}</td>
                            <td>{{ $app->studentProfile->user->name }}</td>
                            <td>{{ $app->programme->name }}</td>
                            <td>{{ $app->status->label() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.ocrs')
@section('title', 'Student Dashboard')
@section('nav')
    <span style="color:var(--muted);font-size:.875rem;">{{ $user->name }}</span>
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>
@endsection
@section('content')
<div class="container portal">
    <aside class="sidebar">
        <nav>
            <a href="{{ route('student.dashboard') }}" class="active">Dashboard</a>
            <a href="{{ route('student.applications.index') }}">Applications</a>
            <a href="{{ route('student.documents.index') }}">Documents</a>
            <a href="{{ route('student.registrations.index') }}">Course Registration</a>
            <a href="{{ route('student.payments.index') }}">Payments</a>
            <a href="{{ route('student.timetable') }}">Timetable</a>
            <a href="{{ route('student.results') }}">Results</a>
        </nav>
    </aside>
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Welcome, {{ $user->name }}</h1>
        <div class="grid-3">
            <div class="card stat"><strong>{{ $application?->status?->label() ?? 'None' }}</strong><span>Application Status</span></div>
            <div class="card stat"><strong>{{ $registration?->status?->label() ?? 'None' }}</strong><span>Registration Status</span></div>
            <div class="card stat"><strong>{{ $pendingPayments }}</strong><span>Pending Payments</span></div>
        </div>
        @if($activeIntake)
            <div class="card">
                <h3>Active Intake: {{ $activeIntake->name }}</h3>
                <p>Applications close {{ $activeIntake->application_closes->format('d M Y') }}.</p>
                <a href="{{ route('student.applications.create') }}" class="btn btn-primary" style="margin-top:.75rem;">Submit Application</a>
            </div>
        @endif
    </div>
</div>
@endsection

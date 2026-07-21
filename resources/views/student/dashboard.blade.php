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

        @if($application)
        <div class="card mt-4">
            <h3>Application Progress</h3>
            <div style="display: flex; justify-content: space-between; margin-top: 1rem; position: relative;">
                <div style="position: absolute; top: 12px; left: 0; right: 0; height: 4px; background: var(--border); z-index: 1;"></div>
                @php
                    $steps = ['Draft', 'Submitted', 'Under Review', 'Approved'];
                    $currentStatusLabel = $application->status->label();
                    $currentIndex = array_search($currentStatusLabel, $steps);
                    if ($currentStatusLabel === 'Pending Application Fee') $currentIndex = 0;
                    if ($currentStatusLabel === 'Waitlisted' || $currentStatusLabel === 'More Information Required') $currentIndex = 2;
                    if ($currentStatusLabel === 'Rejected' || $currentStatusLabel === 'Withdrawn') $currentIndex = -1;
                @endphp
                @foreach($steps as $index => $step)
                    <div style="position: relative; z-index: 2; text-align: center; width: 120px;">
                        <div style="width: 28px; height: 28px; margin: 0 auto 8px; border-radius: 50%; background: {{ $currentIndex >= $index ? 'var(--primary)' : 'var(--border)' }}; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">{{ $index + 1 }}</div>
                        <span style="font-size: 0.85rem; font-weight: {{ $currentIndex >= $index ? 'bold' : 'normal' }}; color: {{ $currentIndex >= $index ? 'var(--text-main)' : 'var(--muted)' }}">{{ $step }}</span>
                    </div>
                @endforeach
            </div>
            @if(in_array($currentStatusLabel, ['Waitlisted', 'More Information Required', 'Rejected', 'Pending Application Fee']))
                <div class="mt-4" style="padding: 1rem; background: #fff3cd; border-radius: 4px; color: #856404;">
                    <strong>Attention:</strong> Your application is currently <u>{{ $currentStatusLabel }}</u>. 
                    @if($application->rejection_reason) <br> Reason: {{ $application->rejection_reason }} @endif
                    @if($currentStatusLabel === 'Pending Application Fee')
                        <br><br><a href="{{ route('student.payments.index') }}" class="btn" style="background:#856404;color:white;border:none;">Pay Application Fee</a>
                    @endif
                </div>
            @endif
            @if($currentStatusLabel === 'Approved')
                @php
                    $letter = \App\Models\AdmissionLetter::where('application_id', $application->id)->first();
                @endphp
                @if($letter)
                    <div class="mt-4">
                        <a href="#" class="btn btn-primary" onclick="alert('Downloading {{ $letter->letter_path }}')">Download Admission Letter</a>
                    </div>
                @endif
            @endif
        </div>
        @endif
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

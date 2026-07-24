@extends('layouts.ocrs')
@section('title', 'Student Dashboard')
@section('nav')
    <span style="color:var(--muted);font-size:.875rem;">{{ $user->name }}</span>
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>
@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'dashboard'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Welcome, {{ $user->name }}</h1>
        <div class="grid-3">
            <div class="card stat"><strong>{{ $application?->status?->label() ?? 'None' }}</strong><span>Application Status</span></div>
            <div class="card stat"><strong>{{ $registration?->status?->label() ?? 'None' }}</strong><span>Registration Status</span></div>
            <div class="card stat"><strong>{{ $pendingPayments }}</strong><span>Pending Payments</span></div>
        </div>

        @if($nextStep)
        <div class="card mt-4" style="border-left: 4px solid var(--primary);">
            <h3 style="margin-top:0;">Next step: {{ $nextStep['title'] }}</h3>
            <p style="color:var(--muted);">{{ $nextStep['message'] }}</p>
            <div style="display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
                <a href="{{ route($nextStep['route']) }}" class="btn btn-primary">{{ $nextStep['cta'] }}</a>
                @if(!empty($nextStep['secondary_route']))
                    <a href="{{ route($nextStep['secondary_route'], $nextStep['secondary_param']) }}" class="btn btn-outline">{{ $nextStep['secondary_cta'] }}</a>
                @endif
            </div>
        </div>
        @endif

        @if($application)
        <div class="card mt-4">
            <h3>Admission &amp; Registration Journey</h3>
            <p style="font-size:.9rem;color:var(--muted);margin-bottom:1rem;">Kenyan direct-admission path: apply → fee → review → enroll (docs + tuition in parallel) → register units.</p>
            <div style="display: flex; justify-content: space-between; margin-top: 1rem; position: relative; flex-wrap: wrap; gap: 0.5rem;">
                <div style="position: absolute; top: 12px; left: 0; right: 0; height: 4px; background: var(--border); z-index: 1;"></div>
                @php
                    $steps = ['Apply', 'Fee', 'Review', 'Enroll', 'Register'];
                    $status = $application->status->value;
                    $currentIndex = match (true) {
                        in_array($status, ['draft', 'pending_fee']) => 0,
                        in_array($status, ['submitted', 'under_review', 'more_info_required', 'waitlisted']) => 2,
                        $status === 'approved' && ! $isEnrolled => 3,
                        $status === 'approved' && $isEnrolled && ! ($registration?->status?->value === 'confirmed') => 4,
                        $status === 'approved' && ($registration?->status?->value === 'confirmed') => 5,
                        $status === 'rejected' => -1,
                        default => 1,
                    };
                    if ($status === 'pending_fee') {
                        $currentIndex = 1;
                    }
                @endphp
                @foreach($steps as $index => $step)
                    <div style="position: relative; z-index: 2; text-align: center; width: 100px;">
                        <div style="width: 28px; height: 28px; margin: 0 auto 8px; border-radius: 50%; background: {{ $currentIndex > $index ? 'var(--primary)' : ($currentIndex === $index ? '#d97706' : 'var(--border)') }}; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">{{ $index + 1 }}</div>
                        <span style="font-size: 0.85rem; font-weight: {{ $currentIndex >= $index ? 'bold' : 'normal' }}; color: {{ $currentIndex >= $index ? 'var(--text-main)' : 'var(--muted)' }}">{{ $step }}</span>
                    </div>
                @endforeach
            </div>

            @if(in_array($application->status->value, ['waitlisted', 'more_info_required', 'rejected', 'pending_fee']))
                <div class="mt-4" style="padding: 1rem; background: #fff3cd; border-radius: 4px; color: #856404;">
                    <strong>Attention:</strong> Your application is currently <u>{{ $application->status->label() }}</u>.
                    @if($application->rejection_reason) <br> Note: {{ $application->rejection_reason }} @endif
                    @if($application->status->value === 'pending_fee')
                        <br><br><a href="{{ route('student.payments.index') }}" class="btn" style="background:#856404;color:white;border:none;">Pay Application Fee</a>
                        <a href="{{ route('student.documents.index') }}" class="btn btn-outline" style="margin-left:.5rem;">Upload documents</a>
                    @endif
                </div>
            @endif

            @if($application->status->value === 'approved')
                <div class="mt-4" style="display:flex; gap:.75rem; flex-wrap:wrap;">
                    <a href="{{ route('student.applications.letter', $application) }}" class="btn btn-primary">Download Admission Letter</a>
                    <a href="{{ route('student.enrollment.index') }}" class="btn btn-outline">Enrollment checklist</a>
                </div>
            @endif
        </div>
        @endif

        @if($activeIntake && ! $application)
            <div class="card">
                <h3>Active Intake: {{ $activeIntake->name }}</h3>
                <p>Applications close {{ $activeIntake->application_closes->format('d M Y') }}.</p>
                <a href="{{ route('student.applications.create') }}" class="btn btn-primary" style="margin-top:.75rem;">Submit Application</a>
            </div>
        @endif
    </div>
</div>
@endsection

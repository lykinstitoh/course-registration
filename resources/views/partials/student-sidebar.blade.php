@php
    $profile = auth()->user()->studentProfile;
    $isAdmitted = $profile?->applications()->where('status', 'approved')->exists();
    $hasApplication = $profile?->applications()->exists();
    $hasPendingFee = $profile?->applications()->where('status', 'pending_fee')->exists();
    $isEnrolled = $profile?->isEnrolled() ?? false;
    $hasConfirmedRegistration = $profile?->registrations()->where('status', 'confirmed')->exists();
@endphp
<aside class="sidebar">
    <nav>
        <a href="{{ route('student.dashboard') }}" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('student.applications.index') }}" class="{{ ($active ?? '') === 'applications' ? 'active' : '' }}">My Applications</a>
        {{-- Documents available as soon as an application exists so uploads never block fees/review --}}
        @if($hasApplication)
            <a href="{{ route('student.documents.index') }}" class="{{ ($active ?? '') === 'documents' ? 'active' : '' }}">My Documents</a>
        @endif
        @if($isAdmitted)
            <a href="{{ route('student.enrollment.index') }}" class="{{ ($active ?? '') === 'enrollment' ? 'active' : '' }}">Enrollment</a>
        @endif
        @if($isEnrolled)
            <a href="{{ route('student.registrations.index') }}" class="{{ ($active ?? '') === 'registrations' ? 'active' : '' }}">Course Registration</a>
        @elseif($isAdmitted)
            <a href="{{ route('student.enrollment.index') }}" class="{{ ($active ?? '') === 'registrations' ? 'active' : '' }}" title="Complete enrollment first">Course Registration</a>
        @endif
        @if($hasConfirmedRegistration)
            <a href="{{ route('student.timetable') }}" class="{{ ($active ?? '') === 'timetable' ? 'active' : '' }}">Timetable</a>
            <a href="{{ route('student.results') }}" class="{{ ($active ?? '') === 'results' ? 'active' : '' }}">Results</a>
        @endif
        @if($isAdmitted || $hasPendingFee || $profile?->payments()->exists())
            <a href="{{ route('student.payments.index') }}" class="{{ ($active ?? '') === 'payments' ? 'active' : '' }}">Payments</a>
        @endif
    </nav>
</aside>

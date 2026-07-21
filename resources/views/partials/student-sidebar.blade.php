@php
    $profile = auth()->user()->studentProfile;
    $isAdmitted = $profile?->applications()->where('status', 'approved')->exists();
    $hasConfirmedRegistration = $profile?->registrations()->where('status', 'confirmed')->exists();
    $hasPendingFee = $profile?->applications()->where('status', 'pending_fee')->exists();
    $showPayments = $isAdmitted || $hasPendingFee || $profile?->payments()->exists();
@endphp
<aside class="sidebar">
    <nav>
        <a href="{{ route('student.dashboard') }}" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('student.applications.index') }}" class="{{ ($active ?? '') === 'applications' ? 'active' : '' }}">My Applications</a>
        @if(auth()->user()->studentProfile?->applications()->where('status', 'approved')->exists())
            <a href="{{ route('student.documents.index') }}" class="{{ ($active ?? '') === 'documents' ? 'active' : '' }}">My Documents</a>
            <a href="{{ route('student.enrollment.index') }}" class="{{ ($active ?? '') === 'enrollment' ? 'active' : '' }}">Enrollment</a>
        @endif
        @if(auth()->user()->studentProfile?->isEnrolled())
            <a href="{{ route('student.registrations.index') }}" class="{{ ($active ?? '') === 'registrations' ? 'active' : '' }}">Course Registration</a>
            <a href="{{ route('student.timetable') }}" class="{{ ($active ?? '') === 'timetable' ? 'active' : '' }}">Timetable</a>
            <a href="{{ route('student.results') }}" class="{{ ($active ?? '') === 'results' ? 'active' : '' }}">Results</a>
        @else
            <a href="#" class="disabled" style="opacity: 0.5; cursor: not-allowed;" title="Complete enrollment first">Course Registration 🔒</a>
            <a href="#" class="disabled" style="opacity: 0.5; cursor: not-allowed;" title="Complete enrollment first">Timetable 🔒</a>
            <a href="#" class="disabled" style="opacity: 0.5; cursor: not-allowed;" title="Complete enrollment first">Results 🔒</a>
        @endif
        <a href="{{ route('student.payments.index') }}" class="{{ ($active ?? '') === 'payments' ? 'active' : '' }}">Payments</a>
    </nav>
</aside>

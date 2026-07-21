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
        <a href="{{ route('student.applications.index') }}" class="{{ ($active ?? '') === 'applications' ? 'active' : '' }}">Applications</a>
        <a href="{{ route('student.documents.index') }}" class="{{ ($active ?? '') === 'documents' ? 'active' : '' }}">Documents</a>
        
        @if($showPayments)
            <a href="{{ route('student.payments.index') }}" class="{{ ($active ?? '') === 'payments' ? 'active' : '' }}">Payments</a>
        @endif

        @if($isAdmitted)
            <a href="{{ route('student.registrations.index') }}" class="{{ ($active ?? '') === 'registrations' ? 'active' : '' }}">Course Registration</a>
        @endif

        @if($hasConfirmedRegistration)
            <a href="{{ route('student.timetable') }}" class="{{ ($active ?? '') === 'timetable' ? 'active' : '' }}">Timetable</a>
            <a href="{{ route('student.results') }}" class="{{ ($active ?? '') === 'results' ? 'active' : '' }}">Results</a>
        @endif
    </nav>
</aside>

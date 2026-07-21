<aside class="sidebar">
    <nav>
        <a href="{{ route('student.dashboard') }}" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('student.applications.index') }}" class="{{ ($active ?? '') === 'applications' ? 'active' : '' }}">Applications</a>
        <a href="{{ route('student.documents.index') }}" class="{{ ($active ?? '') === 'documents' ? 'active' : '' }}">Documents</a>
        <a href="{{ route('student.registrations.index') }}" class="{{ ($active ?? '') === 'registrations' ? 'active' : '' }}">Course Registration</a>
        <a href="{{ route('student.payments.index') }}" class="{{ ($active ?? '') === 'payments' ? 'active' : '' }}">Payments</a>
        <a href="{{ route('student.timetable') }}" class="{{ ($active ?? '') === 'timetable' ? 'active' : '' }}">Timetable</a>
        <a href="{{ route('student.results') }}" class="{{ ($active ?? '') === 'results' ? 'active' : '' }}">Results</a>
    </nav>
</aside>

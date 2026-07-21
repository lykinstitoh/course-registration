<aside class="sidebar">
    <nav>
        <a href="{{ route('admin.dashboard') }}" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('admin.applications.index') }}" class="{{ ($active ?? '') === 'applications' ? 'active' : '' }}">Applications</a>
        <a href="{{ route('admin.intakes.index') }}" class="{{ ($active ?? '') === 'intakes' ? 'active' : '' }}">Intakes</a>
        <a href="{{ route('admin.fees.index') }}" class="{{ ($active ?? '') === 'fees' ? 'active' : '' }}">Fee Structures</a>
        <a href="{{ route('admin.documents.index') }}" class="{{ ($active ?? '') === 'documents' ? 'active' : '' }}">Documents</a>
        <a href="{{ route('admin.reports.index') }}" class="{{ ($active ?? '') === 'reports' ? 'active' : '' }}">Reports</a>
        <a href="{{ route('admin.campuses.index') }}" class="{{ ($active ?? '') === 'campuses' ? 'active' : '' }}">Campuses</a>
        <a href="{{ route('admin.settings.index') }}" class="{{ ($active ?? '') === 'settings' ? 'active' : '' }}">Settings</a>
    </nav>
</aside>

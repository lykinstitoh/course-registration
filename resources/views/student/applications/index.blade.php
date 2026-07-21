@extends('layouts.ocrs')
@section('title', 'My Applications')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'applications'])
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h1 style="color:var(--primary);">My Applications</h1>
            <a href="{{ route('student.applications.create') }}" class="btn btn-primary">New Application</a>
        </div>
        <div class="card">
            <table>
                <thead><tr><th>Reference</th><th>Programme</th><th>Intake</th><th>Status</th><th>Submitted</th></tr></thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr>
                            <td>{{ $app->reference }}</td>
                            <td>{{ $app->programme->name }}</td>
                            <td>{{ $app->intake->name }}</td>
                            <td><span class="badge badge-amber">{{ $app->status->label() }}</span></td>
                            <td>{{ $app->submitted_at?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No applications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

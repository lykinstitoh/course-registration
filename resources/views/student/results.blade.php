@extends('layouts.ocrs')
@section('title', 'Results')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'results'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Academic Results</h1>

        <div class="grid-3" style="margin-bottom:1rem;">
            <div class="card stat">
                <strong>{{ $overall['gpa'] !== null ? number_format($overall['gpa'], 2) : '—' }}</strong>
                <span>Cumulative GPA (4.0)</span>
            </div>
            <div class="card stat">
                <strong>{{ $overall['credits'] }}</strong>
                <span>Credits completed</span>
            </div>
            <div class="card stat">
                <strong>{{ $results->count() }}</strong>
                <span>Published units</span>
            </div>
        </div>

        @forelse($grouped as $semesterId => $semesterResults)
            @php
                $semester = $semesterResults->first()->semester;
                $summary = $semesterSummaries[$semesterId] ?? ['gpa' => null, 'credits' => 0];
            @endphp
            <div class="card">
                <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <div>
                        <h2 style="margin:0;">{{ $semester->name }}</h2>
                        <p style="color:var(--muted);margin:0;">{{ $semester->intake->name ?? '' }}</p>
                    </div>
                    <div style="text-align:right;">
                        <strong>GPA: {{ $summary['gpa'] !== null ? number_format($summary['gpa'], 2) : '—' }}</strong><br>
                        <span style="color:var(--muted);font-size:.875rem;">{{ $summary['credits'] }} credit hours</span>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Credits</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($semesterResults as $r)
                            @php $pass = in_array(strtoupper((string) $r->grade), ['A','B','C','D'], true); @endphp
                            <tr>
                                <td><strong>{{ $r->courseUnit->code }}</strong> — {{ $r->courseUnit->name }}</td>
                                <td>{{ $r->courseUnit->credit_units }}</td>
                                <td>{{ $r->marks !== null ? number_format((float) $r->marks, 1) : '—' }}</td>
                                <td>{{ $r->grade ?? '—' }}</td>
                                <td style="color:{{ $pass ? 'var(--success)' : 'var(--danger)' }};">
                                    @if(!$r->grade) —
                                    @elseif($r->grade === 'A') Excellent
                                    @elseif($r->grade === 'B') Good
                                    @elseif($r->grade === 'C') Satisfactory
                                    @elseif($r->grade === 'D') Pass
                                    @else Fail
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="card">
                <p>No published results yet. Results appear here after the academic office publishes marks for your registered units.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

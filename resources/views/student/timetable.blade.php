@extends('layouts.ocrs')
@section('title', 'Timetable')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'timetable'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">My Timetable</h1>

        @if($registrations->count() > 1)
            <div class="card">
                <form method="GET">
                    <div class="form-group" style="max-width:360px;">
                        <label>Semester</label>
                        <select name="semester_id" onchange="this.form.submit()">
                            @foreach($registrations as $reg)
                                <option value="{{ $reg->semester_id }}" @selected($registration->semester_id === $reg->semester_id)>
                                    {{ $reg->semester->name }} — {{ $reg->semester->intake->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        @endif

        <div class="card">
            <h2 style="margin-bottom:.25rem;">{{ $registration->semester->name }}</h2>
            <p style="color:var(--muted);margin-bottom:1rem;">
                {{ $registration->semester->intake->name ?? '' }}
                · Registration {{ $registration->reference }}
            </p>

            @if($entries->isEmpty())
                <p>No class schedule has been published for your registered units yet. Check again after the academic office posts the timetable.</p>
            @else
                <div style="display:grid;gap:1rem;">
                    @foreach($days as $day)
                        @php $dayEntries = $byDay->get($day, collect()); @endphp
                        @if($dayEntries->isNotEmpty())
                            <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                                <div style="background:rgba(11,61,46,.08);padding:.6rem 1rem;font-weight:600;color:var(--primary);">{{ $day }}</div>
                                <table style="margin:0;">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Unit</th>
                                            <th>Venue</th>
                                            <th>Lecturer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($dayEntries as $e)
                                            <tr>
                                                <td>{{ $e->timeRange() }}</td>
                                                <td><strong>{{ $e->courseUnit->code }}</strong> — {{ $e->courseUnit->name }}</td>
                                                <td>{{ $e->venue }}</td>
                                                <td>{{ $e->lecturer ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endforeach
                </div>

                <h3 style="margin-top:1.5rem;">All sessions</h3>
                <table>
                    <thead><tr><th>Day</th><th>Time</th><th>Unit</th><th>Venue</th><th>Lecturer</th></tr></thead>
                    <tbody>
                        @foreach($entries as $e)
                            <tr>
                                <td>{{ $e->day_of_week }}</td>
                                <td>{{ $e->timeRange() }}</td>
                                <td>{{ $e->courseUnit->code }}</td>
                                <td>{{ $e->venue }}</td>
                                <td>{{ $e->lecturer ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection

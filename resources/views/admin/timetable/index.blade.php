@extends('layouts.ocrs')
@section('title', 'Timetable')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'timetable'])
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
            <h1 style="color:var(--primary);">Class Timetable</h1>
            <a href="{{ route('admin.timetable.create', ['semester_id' => $semester?->id]) }}" class="btn btn-primary">Add Slot</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="card">
            <form method="GET" style="display:flex;gap:1rem;align-items:end;flex-wrap:wrap;">
                <div class="form-group" style="min-width:260px;">
                    <label>Semester</label>
                    <select name="semester_id" onchange="this.form.submit()">
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" @selected($semester?->id === $s->id)>
                                {{ $s->name }} — {{ $s->intake->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <div class="card">
            @if(!$semester)
                <p>No semesters available. Create an intake first.</p>
            @elseif($entries->isEmpty())
                <p>No timetable slots for this semester yet.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Unit</th>
                            <th>Venue</th>
                            <th>Lecturer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr>
                                <td>{{ $entry->day_of_week }}</td>
                                <td>{{ $entry->timeRange() }}</td>
                                <td><strong>{{ $entry->courseUnit->code }}</strong> — {{ $entry->courseUnit->name }}</td>
                                <td>{{ $entry->venue }}</td>
                                <td>{{ $entry->lecturer ?: '—' }}</td>
                                <td style="display:flex;gap:.5rem;">
                                    <a href="{{ route('admin.timetable.edit', $entry) }}" class="btn btn-sm btn-outline">Edit</a>
                                    <form method="POST" action="{{ route('admin.timetable.destroy', $entry) }}" onsubmit="return confirm('Delete this slot?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline" style="color:var(--danger);border-color:var(--danger);" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection

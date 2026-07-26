@extends('layouts.ocrs')
@section('title', 'Add Timetable Slot')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'timetable'])
    <div class="card" style="max-width:720px;">
        <h2>Add Timetable Slot</h2>
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('admin.timetable.store') }}">
            @csrf
            <div class="grid-2">
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester_id" required>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" @selected((string) old('semester_id', $selectedSemesterId) === (string) $s->id)>
                                {{ $s->name }} — {{ $s->intake->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Course Unit</label>
                    <select name="course_unit_id" required>
                        <option value="">Select unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) old('course_unit_id') === (string) $unit->id)>
                                {{ $unit->code }} — {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Day</label>
                    <select name="day_of_week" required>
                        @foreach($days as $day)
                            <option value="{{ $day }}" @selected(old('day_of_week') === $day)>{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Venue</label>
                    <input type="text" name="venue" value="{{ old('venue') }}" required placeholder="Lab 1 / LT 2">
                </div>
                <div class="form-group">
                    <label>Starts</label>
                    <input type="time" name="starts_at" value="{{ old('starts_at') }}" required>
                </div>
                <div class="form-group">
                    <label>Ends</label>
                    <input type="time" name="ends_at" value="{{ old('ends_at') }}" required>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Lecturer</label>
                    <input type="text" name="lecturer" value="{{ old('lecturer') }}" placeholder="Optional">
                </div>
            </div>
            <div style="display:flex;gap:.75rem;margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Save Slot</button>
                <a href="{{ route('admin.timetable.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.ocrs')
@section('title', 'Course Registration')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'registrations'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Course Unit Registration</h1>
        @if($activeSemester)
            <div class="card" style="margin-bottom:2rem;">
                <h2 style="color:var(--primary);margin-bottom:1rem;">
                    Register for {{ $activeSemester->name }} 
                    <span class="badge" style="background:#e0e7ff; color:#3730a3; margin-left:1rem;">Current Level: Semester {{ $currentSemesterLevel }}</span>
                </h2>
                <form method="POST" action="{{ route('student.registrations.store') }}">
                    @csrf
                    <input type="hidden" name="semester_id" value="{{ $activeSemester->id }}">
                    <div class="course-grid">
                        @foreach($courseUnits as $unit)
                            <label class="course-item">
                                <input type="checkbox" name="course_unit_ids[]" value="{{ $unit->id }}">
                                <span>{{ $unit->code }} — {{ $unit->name }} ({{ $unit->credit_units }} CU, cap {{ $unit->capacity }})</span>
                            </label>
                        @endforeach
                    </div>
                    <button class="btn btn-primary" type="submit" style="margin-top:1rem;">Submit Registration</button>
                </form>
            </div>
        @else
            <div class="card"><p>No active registration period.</p></div>
        @endif
        <div class="card">
            <h3>Registration History</h3>
            <table>
                <thead><tr><th>Reference</th><th>Semester</th><th>Units</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($registrations as $reg)
                        <tr>
                            <td>{{ $reg->reference }}</td>
                            <td>{{ $reg->semester->name }}</td>
                            <td>{{ $reg->items->pluck('courseUnit.code')->join(', ') }}</td>
                            <td>{{ $reg->status->label() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No registrations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

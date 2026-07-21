@extends('layouts.ocrs')
@section('title', 'Timetable')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'timetable'])
    <div class="card">
        <h2>My Timetable</h2>
        @if($entries->isEmpty())
            <p>No timetable available. Complete course registration first.</p>
        @else
            <table>
                <thead><tr><th>Day</th><th>Time</th><th>Unit</th><th>Venue</th><th>Lecturer</th></tr></thead>
                <tbody>
                    @foreach($entries as $e)
                        <tr>
                            <td>{{ $e->day_of_week }}</td>
                            <td>{{ substr($e->starts_at, 0, 5) }}–{{ substr($e->ends_at, 0, 5) }}</td>
                            <td>{{ $e->courseUnit->code }}</td>
                            <td>{{ $e->venue }}</td>
                            <td>{{ $e->lecturer }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

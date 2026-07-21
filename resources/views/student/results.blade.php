@extends('layouts.ocrs')
@section('title', 'Results')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'results'])
    <div class="card">
        <h2>Academic Results</h2>
        <table>
            <thead><tr><th>Unit</th><th>Semester</th><th>Marks</th><th>Grade</th></tr></thead>
            <tbody>
                @forelse($results as $r)
                    <tr>
                        <td>{{ $r->courseUnit->code }} — {{ $r->courseUnit->name }}</td>
                        <td>{{ $r->semester->name }}</td>
                        <td>{{ $r->marks ?? '—' }}</td>
                        <td>{{ $r->grade ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No published results yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

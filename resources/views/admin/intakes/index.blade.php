@extends('layouts.ocrs')
@section('title', 'Manage Intakes')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'intakes'])
    <div>
        <div class="card">
            <h2>Create Intake</h2>
            <form method="POST" action="{{ route('admin.intakes.store') }}">
                @csrf
                <div class="grid-2">
                    <div class="form-group"><label>Name</label><input name="name" required placeholder="September 2026"></div>
                    <div class="form-group"><label>Academic Year</label><input name="academic_year" required placeholder="2026/2027"></div>
                    <div class="form-group"><label>Application Opens</label><input type="date" name="application_opens" required></div>
                    <div class="form-group"><label>Application Closes</label><input type="date" name="application_closes" required></div>
                    <div class="form-group"><label>Registration Opens</label><input type="date" name="registration_opens"></div>
                    <div class="form-group"><label>Registration Closes</label><input type="date" name="registration_closes"></div>
                </div>
                <button class="btn btn-primary" type="submit">Create Intake</button>
            </form>
        </div>
        <div class="card">
            <h3>Existing Intakes</h3>
            <table>
                <thead><tr><th>Name</th><th>Year</th><th>Applications</th><th>Semesters</th></tr></thead>
                <tbody>
                    @foreach($intakes as $intake)
                        <tr>
                            <td>{{ $intake->name }}</td>
                            <td>{{ $intake->academic_year }}</td>
                            <td>{{ $intake->application_opens->format('d M') }} – {{ $intake->application_closes->format('d M Y') }}</td>
                            <td>{{ $intake->semesters->pluck('name')->join(', ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

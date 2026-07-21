@extends('layouts.ocrs')
@section('title', 'New Application')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'applications'])
    <div class="card">
        <h2>Programme Application</h2>
        <form method="POST" action="{{ route('student.applications.store') }}">
            @csrf
            <div class="grid-2">
                <div class="form-group"><label>Programme</label>
                    <select name="programme_id" required>
                        <option value="">Select programme</option>
                        @foreach($programmes as $p)<option value="{{ $p->id }}">{{ $p->name }} (Min KCSE: {{ $p->minimum_kcse_grade }})</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>Intake</label>
                    <select name="intake_id" required>
                        @foreach($intakes as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>KCSE Mean Grade (points)</label><input type="number" step="0.01" name="kcse_mean_grade" value="{{ old('kcse_mean_grade') }}" required></div>
                <div class="form-group"><label>KCSE Index Number</label><input type="text" name="kcse_index_number" value="{{ old('kcse_index_number') }}" inputmode="numeric" pattern="[0-9]*" maxlength="30" title="Enter numbers only" required></div>
                <div class="form-group"><label>KCSE Year</label>
                    <select name="kcse_year" required>
                        <option value="">Select KCSE examination year</option>
                        @foreach($kcseYears as $year)<option value="{{ $year }}" @selected((string) old('kcse_year') === (string) $year)>{{ $year }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>National ID</label><input type="text" name="national_id" value="{{ old('national_id') }}" inputmode="numeric" pattern="[0-9]*" maxlength="20" title="Enter numbers only" required></div>
                <div class="form-group"><label>County</label>
                    <select name="county" required>
                        <option value="">Select county</option>
                        @foreach($counties as $county)<option value="{{ $county }}" @selected(old('county') === $county)>{{ $county }}</option>@endforeach
                    </select>
                </div>
            </div>
            <button class="btn btn-accent" type="submit">Submit Application</button>
        </form>
    </div>
</div>
@endsection

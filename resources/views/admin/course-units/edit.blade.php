@extends('layouts.ocrs')
@section('title', 'Edit Course Unit')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'course-units'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Edit Course Unit: {{ $courseUnit->code }}</h1>
        <div class="card">
            <form method="POST" action="{{ route('admin.course-units.update', $courseUnit) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="code" class="form-control" required value="{{ old('code', $courseUnit->code) }}">
                </div>
                
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name', $courseUnit->name) }}">
                </div>

                <div class="form-group">
                    <label>Semester Level</label>
                    <input type="number" name="semester_level" class="form-control" required min="1" value="{{ old('semester_level', $courseUnit->semester_level) }}">
                    <small>e.g. 1 for First Semester, 2 for Second Semester, etc.</small>
                </div>

                <div class="form-group">
                    <label>Associated Programmes</label>
                    <select name="programme_ids[]" class="form-control" multiple style="height: 120px;">
                        @foreach($programmes as $programme)
                            <option value="{{ $programme->id }}" {{ in_array($programme->id, old('programme_ids', $courseUnit->programmes->pluck('id')->toArray())) ? 'selected' : '' }}>
                                {{ $programme->name }} ({{ $programme->code }})
                            </option>
                        @endforeach
                    </select>
                    <small>Hold Ctrl (Windows) or Cmd (Mac) to select multiple programmes.</small>
                </div>

                <div class="form-group">
                    <label>Credit Units</label>
                    <input type="number" name="credit_units" class="form-control" required min="1" value="{{ old('credit_units', $courseUnit->credit_units) }}">
                </div>

                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" class="form-control" required min="1" value="{{ old('capacity', $courseUnit->capacity) }}">
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $courseUnit->is_active) ? 'checked' : '' }}>
                        Active
                    </label>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Update Course Unit</button>
                    <a href="{{ route('admin.course-units.index') }}" class="btn btn-outline" style="margin-left: 1rem;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

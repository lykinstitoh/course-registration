@extends('layouts.ocrs')
@section('title', 'Add Course Unit')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'course-units'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Add Course Unit</h1>
        <div class="card">
            <form method="POST" action="{{ route('admin.course-units.store') }}">
                @csrf
                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="code" class="form-control" required value="{{ old('code') }}" placeholder="e.g. CSC101">
                </div>
                
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="e.g. Introduction to Programming">
                </div>

                <div class="form-group">
                    <label>Semester Level</label>
                    <input type="number" name="semester_level" class="form-control" required min="1" value="{{ old('semester_level', 1) }}">
                    <small>e.g. 1 for First Semester, 2 for Second Semester, etc.</small>
                </div>

                <div class="form-group">
                    <label>Credit Units</label>
                    <input type="number" name="credit_units" class="form-control" required min="1" value="{{ old('credit_units', 3) }}">
                </div>

                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" class="form-control" required min="1" value="{{ old('capacity', 50) }}">
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        Active
                    </label>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">Save Course Unit</button>
                    <a href="{{ route('admin.course-units.index') }}" class="btn btn-outline" style="margin-left: 1rem;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

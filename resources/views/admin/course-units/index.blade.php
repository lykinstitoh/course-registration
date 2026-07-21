@extends('layouts.ocrs')
@section('title', 'Course Units')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'course-units'])
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h1 style="color:var(--primary);">Course Units by Semester</h1>
            <a href="{{ route('admin.course-units.create') }}" class="btn btn-primary">Add Course Unit</a>
        </div>

        @forelse($courseUnits as $level => $units)
            <div class="card" style="margin-bottom: 2rem;">
                <h2 style="border-bottom: 2px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem; color: var(--primary);">
                    Semester {{ $level }}
                </h2>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Credits</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($units as $unit)
                            <tr>
                                <td><strong>{{ $unit->code }}</strong></td>
                                <td>{{ $unit->name }}</td>
                                <td>{{ $unit->credit_units }}</td>
                                <td>{{ $unit->capacity }}</td>
                                <td>
                                    @if($unit->is_active)
                                        <span class="badge" style="background:#dcfce7; color:#166534;">Active</span>
                                    @else
                                        <span class="badge badge-amber">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:0.5rem;">
                                        <a href="{{ route('admin.course-units.edit', $unit) }}" class="btn btn-sm btn-outline">Edit</a>
                                        <form method="POST" action="{{ route('admin.course-units.destroy', $unit) }}" onsubmit="return confirm('Delete this course unit?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger); border-color:var(--danger);">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <div class="card">
                <p>No course units found. Add some to get started.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

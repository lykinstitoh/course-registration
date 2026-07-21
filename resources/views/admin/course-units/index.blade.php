@extends('layouts.ocrs')
@section('title', 'Course Units')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'course-units'])
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h1 style="color:var(--primary);">Course Units by Programme</h1>
            <a href="{{ route('admin.course-units.create') }}" class="btn btn-primary">Add Course Unit</a>
        </div>

        @foreach($programmes as $programme)
            <div class="card" style="margin-bottom: 2rem;">
                <h2 style="border-bottom: 3px solid var(--primary); padding-bottom: 0.5rem; margin-bottom: 1.5rem; color: var(--primary);">
                    {{ $programme->name }} <span style="color:#666; font-size:1rem;">({{ $programme->code }})</span>
                </h2>

                @php
                    $semesters = $programme->courseUnits->groupBy('semester_level');
                @endphp

                @forelse($semesters as $level => $units)
                    <div style="margin-bottom: 2rem;">
                        <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem; color: #4b5563;">
                            Semester {{ $level }}
                        </h3>
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
                    <p style="color:#666;">No course units assigned to this programme yet.</p>
                @endforelse
            </div>
        @endforeach

        @if($unassignedUnits->isNotEmpty())
            <div class="card" style="margin-bottom: 2rem; border: 2px dashed #f59e0b;">
                <h2 style="border-bottom: 3px solid #f59e0b; padding-bottom: 0.5rem; margin-bottom: 1.5rem; color: #b45309;">
                    Unassigned Course Units
                </h2>
                
                @php
                    $unassignedSemesters = $unassignedUnits->groupBy('semester_level');
                @endphp

                @foreach($unassignedSemesters as $level => $units)
                    <div style="margin-bottom: 2rem;">
                        <h3 style="border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1rem; color: #4b5563;">
                            Semester {{ $level }}
                        </h3>
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
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection

@extends('layouts.ocrs')
@section('title', 'Manage Campuses')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'campuses'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Manage Campuses</h1>

        <div class="card mb-4">
            <h3>Add New Campus</h3>
            <form method="POST" action="{{ route('admin.campuses.store') }}">
                @csrf
                <div class="grid-3" style="align-items:end;">
                    <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                    <div class="form-group"><label>Code</label><input type="text" name="code" required></div>
                    <div class="form-group"><label>Location</label><input type="text" name="location"></div>
                </div>
                <div class="form-group mt-2">
                    <label><input type="checkbox" name="is_active" checked> Is Active</label>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Add Campus</button>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr><th>Name</th><th>Code</th><th>Location</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @foreach($campuses as $campus)
                        <tr>
                            <td>{{ $campus->name }}</td>
                            <td>{{ $campus->code }}</td>
                            <td>{{ $campus->location }}</td>
                            <td>{{ $campus->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.campuses.update', $campus) }}" style="display:inline-block; margin-bottom: 5px;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="name" value="{{ $campus->name }}">
                                    <input type="hidden" name="code" value="{{ $campus->code }}">
                                    <input type="hidden" name="location" value="{{ $campus->location }}">
                                    @if(!$campus->is_active) <input type="hidden" name="is_active" value="1"> @endif
                                    <button type="submit" class="btn btn-outline">{{ $campus->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.campuses.destroy', $campus) }}" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this campus?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary" style="background:#e3342f;color:white;border-color:#e3342f;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

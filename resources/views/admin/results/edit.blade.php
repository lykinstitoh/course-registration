@extends('layouts.ocrs')
@section('title', 'Edit Result')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'results'])
    <div class="card" style="max-width:640px;">
        <h2>Edit Result</h2>
        <p style="margin-bottom:1rem;color:var(--muted);">
            {{ $result->studentProfile->user->name }} —
            <strong>{{ $result->courseUnit->code }}</strong> ({{ $result->semester->name }})
        </p>
        <form method="POST" action="{{ route('admin.results.update', $result) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Marks (%)</label>
                <input type="number" name="marks" step="0.01" min="0" max="100" value="{{ old('marks', $result->marks) }}" required>
                <small style="color:var(--muted);">Grade is auto-assigned: A≥70, B≥60, C≥50, D≥40, F&lt;40.</small>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="pending" @selected(old('status', $result->status->value) === 'pending')>Pending</option>
                    <option value="published" @selected(old('status', $result->status->value) === 'published')>Published</option>
                </select>
            </div>
            <div style="display:flex;gap:.75rem;margin-top:1rem;">
                <button class="btn btn-primary" type="submit">Save</button>
                <a href="{{ route('admin.results.index', ['semester_id' => $result->semester_id]) }}" class="btn btn-outline">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection

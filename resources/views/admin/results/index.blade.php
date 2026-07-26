@extends('layouts.ocrs')
@section('title', 'Results')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'results'])
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
            <h1 style="color:var(--primary);">Student Results</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <form method="GET" style="display:flex;gap:1rem;align-items:end;flex-wrap:wrap;margin-bottom:1rem;">
                <div class="form-group" style="min-width:240px;">
                    <label>Semester</label>
                    <select name="semester_id" onchange="this.form.submit()">
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" @selected($semester?->id === $s->id)>
                                {{ $s->name }} — {{ $s->intake->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                        <option value="published" @selected($statusFilter === 'published')>Published</option>
                    </select>
                </div>
            </form>

            @if($semester)
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <form method="POST" action="{{ route('admin.results.generate') }}">
                        @csrf
                        <input type="hidden" name="semester_id" value="{{ $semester->id }}">
                        <button class="btn btn-outline" type="submit">Generate from registrations</button>
                    </form>
                    <form method="POST" action="{{ route('admin.results.publish') }}" onsubmit="return confirm('Publish all graded pending results for this semester?');">
                        @csrf
                        <input type="hidden" name="semester_id" value="{{ $semester->id }}">
                        <button class="btn btn-primary" type="submit">Publish graded results</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="card">
            @if(!$semester)
                <p>No semesters available.</p>
            @elseif($results->isEmpty())
                <p>No result records yet. Generate them from confirmed course registrations.</p>
            @else
                <form method="POST" action="{{ route('admin.results.bulk') }}">
                    @csrf
                    <input type="hidden" name="semester_id" value="{{ $semester->id }}">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Admission No.</th>
                                <th>Unit</th>
                                <th>Credits</th>
                                <th>Marks</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr>
                                    <td>{{ $result->studentProfile->user->name }}</td>
                                    <td>{{ $result->studentProfile->admission_number ?: '—' }}</td>
                                    <td><strong>{{ $result->courseUnit->code }}</strong></td>
                                    <td>{{ $result->courseUnit->credit_units }}</td>
                                    <td style="min-width:110px;">
                                        <input type="number" step="0.01" min="0" max="100" name="marks[{{ $result->id }}]" value="{{ $result->marks }}" style="width:100%;">
                                    </td>
                                    <td>{{ $result->grade ?: '—' }}</td>
                                    <td>{{ $result->status->label() }}</td>
                                    <td><a href="{{ route('admin.results.edit', $result) }}" class="btn btn-sm btn-outline">Edit</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-accent" type="submit" style="margin-top:1rem;">Save marks</button>
                </form>
                <div style="margin-top:1rem;">{{ $results->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

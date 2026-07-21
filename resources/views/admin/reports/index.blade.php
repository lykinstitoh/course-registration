@extends('layouts.ocrs')
@section('title', 'Reports')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'reports'])
    <div>
        <div class="card">
            <h2>CUE & Management Reports</h2>
            <p style="color:var(--muted);margin-bottom:1rem;">Enrollment and financial summaries for statutory and management reporting.</p>
            <h3>Enrollment by Programme</h3>
            <table>
                <thead><tr><th>Programme</th><th>Approved Applications</th><th>CUE Reference</th></tr></thead>
                <tbody>
                    @foreach($enrollmentByProgramme as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td>{{ $p->approved_count }}</td>
                            <td>{{ $p->cue_accreditation_ref ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="grid-2">
            <div class="card">
                <h3>Revenue by Payment Method</h3>
                <table>
                    @foreach($revenueSummary as $row)
                        <tr><td>{{ $row->method }}</td><td>KES {{ number_format($row->total) }} ({{ $row->count }} txns)</td></tr>
                    @endforeach
                </table>
            </div>
            <div class="card">
                <h3>Registration Status</h3>
                <table>
                    @foreach($registrationStats as $status => $count)
                        <tr><td>{{ $status }}</td><td>{{ $count }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

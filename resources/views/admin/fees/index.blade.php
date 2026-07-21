@extends('layouts.ocrs')
@section('title', 'Fee Structures')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'fees'])
    <div>
        <div class="card">
            <h2>Configure Fee Structure</h2>
            <form method="POST" action="{{ route('admin.fees.store') }}">
                @csrf
                <div class="grid-2">
                    <div class="form-group"><label>Programme</label><select name="programme_id" required>@foreach($programmes as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Intake</label><select name="intake_id" required>@foreach($intakes as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Fee Type</label><select name="fee_type"><option value="application">Application</option><option value="tuition">Tuition</option><option value="registration">Registration</option><option value="exam">Examination</option></select></div>
                    <div class="form-group"><label>Amount (KES)</label><input type="number" name="amount" step="0.01" required></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Description</label><input name="description" required></div>
                </div>
                <button class="btn btn-primary" type="submit">Add Fee</button>
            </form>
        </div>
        <div class="card">
            <table>
                <thead><tr><th>Programme</th><th>Intake</th><th>Type</th><th>Description</th><th>Amount</th></tr></thead>
                <tbody>
                    @foreach($fees as $fee)
                        <tr>
                            <td>{{ $fee->programme->name }}</td>
                            <td>{{ $fee->intake->name }}</td>
                            <td>{{ $fee->fee_type }}</td>
                            <td>{{ $fee->description }}</td>
                            <td>KES {{ number_format($fee->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

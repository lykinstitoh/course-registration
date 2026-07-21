@extends('layouts.ocrs')
@section('title', 'Enrollment Checklist')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'enrollment'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Enrollment Checklist</h1>
        <p>Complete the following steps to finalize your enrollment and unlock course registration.</p>

        <div class="card" style="margin-top: 2rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <div>
                    <h3 style="margin:0;">1. Application Status</h3>
                    <p style="margin: 0.5rem 0 0 0; color: #666;">Your application to {{ $application->programme->name }}</p>
                </div>
                <div>
                    <span class="badge" style="background:#dcfce7; color:#166534; font-size:1rem; padding:0.5rem 1rem;">&#10003; Approved</span>
                </div>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <div>
                    <h3 style="margin:0;">2. Document Verification</h3>
                    <p style="margin: 0.5rem 0 0 0; color: #666;">All mandatory documents must be uploaded and verified by an admin.</p>
                    <ul style="margin-top: 0.5rem; padding-left: 1.5rem; color: #666;">
                        @foreach($requiredDocs as $req)
                            @php
                                $doc = $studentDocs->get($req->code);
                            @endphp
                            <li>
                                {{ $req->name }} - 
                                @if(!$doc)
                                    <span style="color:var(--danger); font-weight:bold;">Missing</span>
                                @elseif($doc->status->value === 'verified')
                                    <span style="color:#166534; font-weight:bold;">Verified</span>
                                @elseif($doc->status->value === 'rejected')
                                    <span style="color:var(--danger); font-weight:bold;">Rejected</span>
                                @else
                                    <span style="color:#b45309; font-weight:bold;">Pending Review</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    @if($documentsVerified)
                        <span class="badge" style="background:#dcfce7; color:#166534; font-size:1rem; padding:0.5rem 1rem;">&#10003; Verified</span>
                    @else
                        <a href="{{ route('student.documents.index') }}" class="btn btn-outline">Upload Documents</a>
                    @endif
                </div>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom: 1rem;">
                <div>
                    <h3 style="margin:0;">3. Tuition Fee Payment</h3>
                    <p style="margin: 0.5rem 0 0 0; color: #666;">You must pay at least {{ $minPercentage }}% of your total tuition fee to enroll.</p>
                    <ul style="margin-top: 0.5rem; padding-left: 1.5rem; color: #666;">
                        <li>Total Tuition: KES {{ number_format($tuitionFee) }}</li>
                        <li>Required Deposit ({{ $minPercentage }}%): KES {{ number_format($requiredDeposit) }}</li>
                        <li>Total Paid: KES {{ number_format($paidTuition) }}</li>
                    </ul>
                </div>
                <div>
                    @if($feePaid)
                        <span class="badge" style="background:#dcfce7; color:#166534; font-size:1rem; padding:0.5rem 1rem;">&#10003; Paid</span>
                    @else
                        <a href="{{ route('student.payments.index') }}" class="btn btn-primary">Pay Fees</a>
                    @endif
                </div>
            </div>
        </div>

        @if($isEnrolled)
            <div class="alert" style="background:#dcfce7; color:#166534; border:1px solid #bbf7d0; padding:1.5rem; margin-top:2rem; border-radius:8px; text-align:center;">
                <h2 style="margin:0 0 0.5rem 0;">🎉 Congratulations! You are officially enrolled.</h2>
                <p style="margin:0 0 1rem 0;">You have completed all enrollment requirements.</p>
                <a href="{{ route('student.registrations.index') }}" class="btn btn-primary" style="font-size:1.1rem; padding: 0.75rem 2rem;">Proceed to Course Registration</a>
            </div>
        @endif
    </div>
</div>
@endsection

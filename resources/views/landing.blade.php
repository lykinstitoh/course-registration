@extends('layouts.ocrs')

@section('nav')
    <ul class="nav-links">
        <li><a href="#programmes">Programmes</a></li>
        <li><a href="#features">Features</a></li>
        <li><a href="#compliance">Compliance</a></li>
        @auth
            <li><a href="{{ auth()->user()->isStudent() ? route('student.dashboard') : route('admin.dashboard') }}" class="btn btn-primary">Dashboard</a></li>
        @else
            <li><a href="{{ route('login') }}">Sign In</a></li>
            <li><a href="{{ route('register') }}" class="btn btn-accent">Apply Now</a></li>
        @endauth
    </ul>
@endsection

@section('content')
<section style="background:#171d5e;color:#fff;padding:4rem 0;">
    <div class="container">
        <p style="opacity:.85;font-size:.85rem;margin-bottom:.5rem;">Online Course Registration System (OCRS)</p>
        <h1 style="font-size:2.5rem;line-height:1.2;margin-bottom:1rem;">Digital Registration for Self-Sponsored Students in Kenya</h1>
        <p style="max-width:640px;opacity:.9;margin-bottom:1.5rem;">
            End-to-end management of the student lifecycle — from programme application and M-Pesa fee payment
            to course unit registration, timetables, and academic results.
        </p>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
            <a href="{{ route('register') }}" class="btn btn-accent">Start Application</a>
            <a href="{{ route('login') }}" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.4);">Student Portal</a>
        </div>
        @if($activeIntake)
            <p style="margin-top:1.25rem;font-size:.9rem;opacity:.85;">
                {{ $activeIntake->name }} intake open until {{ $activeIntake->application_closes->format('d M Y') }}
            </p>
        @endif
    </div>
</section>

<section id="features" class="container" style="padding:3rem 0;">
    <h2 style="color:#175e4e;margin-bottom:1.5rem;">System Capabilities</h2>
    <div class="grid-3">
        <div class="card"><h3>Student Portal</h3><p>Submit applications, upload credentials, register for units, pay via M-Pesa STK Push or bank transfer, and access timetables and results.</p></div>
        <div class="card"><h3>Administration Panel</h3><p>Registrar, finance, and academic staff manage intakes, approve applications, configure fees, enforce rules, and generate reports.</p></div>
        <div class="card"><h3>Academic Rules Engine</h3><p>Enforces KCSE eligibility, prerequisite chains, unit capacity limits, and semester registration deadlines automatically.</p></div>
        <div class="card"><h3>M-Pesa Integration</h3><p>Daraja API STK Push and C2B for real-time payments and automated fee clearance — no manual confirmation.</p></div>
        <div class="card"><h3>Document Management</h3><p>Secure upload and verification of KCSE certificates and IDs with a full audit trail of review actions.</p></div>
        <div class="card"><h3>Notifications</h3><p>Africa's Talking SMS and SMTP email alerts for application status, payment confirmation, and deadline reminders.</p></div>
    </div>
</section>

<section id="programmes" style="background:#171d5e;padding:3rem 0;">
    <div class="container">
        <h2 style="color:#fff;margin-bottom:1.5rem;">Accredited Programmes</h2>
        <div class="grid-2">
            @forelse($programmes as $programme)
                <div class="card">
                    <span class="badge badge-green">{{ $programme->code }}</span>
                    <h3 style="margin-top:.5rem;">{{ $programme->name }}</h3>
                    <p style="color:var(--muted);font-size:.875rem;">{{ $programme->department }} · Min KCSE: {{ $programme->minimum_kcse_grade }}</p>
                    @if($programme->cue_accreditation_ref)
                        <p style="font-size:.8rem;margin-top:.5rem;">CUE Ref: {{ $programme->cue_accreditation_ref }}</p>
                    @endif
                </div>
            @empty
                <p>Programmes will be listed once configured by the institution.</p>
            @endforelse
        </div>
    </div>
</section>

<section id="compliance" class="container" style="padding:3rem 0;">
    <div class="card">
        <h2>Regulatory Compliance</h2>
        <ul style="margin-left:1.25rem;color:var(--muted);">
            <li>CUE programme accreditation data model and reporting structure</li>
            <li>Kenya Data Protection Act 2019 — consent capture and {{ config('ocrs.data_retention_years') }}-year retention policy</li>
            <li>Institutional financial record retention for fee transactions</li>
            <li>Audit trails for document verification and administrative actions</li>
        </ul>
    </div>
</section>
@endsection

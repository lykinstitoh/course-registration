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
        <p style="opacity:.85;font-size:.85rem;margin-bottom:.5rem;">Online Course Registration System</p>
        <h1 style="font-size:2.5rem;line-height:1.2;margin-bottom:1rem;">Your Journey at {{ $institutionName }} Starts Here</h1>
        <p style="max-width:640px;opacity:.9;margin-bottom:1.5rem;">
            Join our vibrant academic community! Apply for your dream programme, easily manage your fee payments, register for classes, and access your timetable and results—all in one place.
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
    <h2 style="color:#175e4e;margin-bottom:1.5rem;">Everything You Need in One Place</h2>
    <div class="grid-3">
        <div class="card"><h3>Easy Application Process</h3><p>Browse our accredited programmes and submit your application online in just a few clicks.</p></div>
        <div class="card"><h3>Seamless Fee Payments</h3><p>Pay your application and tuition fees securely via M-Pesa STK Push or Bank Transfer with instant confirmation.</p></div>
        <div class="card"><h3>Simple Course Registration</h3><p>Register for your semester units instantly once you are admitted and track your curriculum progress.</p></div>
        <div class="card"><h3>Stay Organized</h3><p>Access your personalized class timetable and view your academic results as soon as they are published.</p></div>
        <div class="card"><h3>Secure Document Uploads</h3><p>Easily upload your KCSE certificates, ID, and other supporting documents directly from your device.</p></div>
        <div class="card"><h3>Instant Notifications</h3><p>Get real-time SMS and email alerts about your application status, payment confirmations, and important deadlines.</p></div>
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
        <h2>Your Data is Secure</h2>
        <p style="color:var(--muted); margin-top:0.5rem; line-height: 1.6;">
            We take your privacy seriously. Your personal information and academic records are securely stored and processed in compliance with the Kenya Data Protection Act. We ensure your data is only used for academic and administrative purposes within {{ $institutionName }}.
        </p>
    </div>
</section>
@endsection

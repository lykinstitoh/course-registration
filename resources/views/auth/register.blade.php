@extends('layouts.ocrs')
@section('title', 'Register')
@section('content')
<div class="container" style="max-width:480px;padding:3rem 0;">
    <div class="card">
        <h2>Create Student Account</h2>
        <p style="color:var(--muted);font-size:.875rem;margin-bottom:1rem;">For self-sponsored applicants at {{ config('ocrs.institution_name') }}.</p>
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="form-group"><label>Full Name</label><input type="text" name="name" value="{{ old('name') }}" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
            <div class="form-group"><label>Phone (M-Pesa)</label><input type="text" name="phone" value="{{ old('phone') }}" placeholder="07XX XXX XXX" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="password_confirmation" required></div>
            <div class="form-group">
                <label><input type="checkbox" name="consent_data_processing" value="1" required>
                    I consent to processing of my personal data per the Kenya Data Protection Act 2019.</label>
            </div>
            <button class="btn btn-accent" type="submit">Create Account</button>
        </form>
    </div>
</div>
@endsection

@extends('layouts.ocrs')
@section('title', 'Sign In')
@section('content')
<div class="container" style="max-width:420px;padding:3rem 0;">
    <div class="card">
        <h2>Sign In to OCRS</h2>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <div class="form-group"><label><input type="checkbox" name="remember"> Remember me</label></div>
            <button class="btn btn-primary" type="submit">Sign In</button>
        </form>
        <p style="margin-top:1rem;font-size:.875rem;">New student? <a href="{{ route('register') }}" style="color:var(--primary);">Create account</a></p>
    </div>
</div>
@endsection

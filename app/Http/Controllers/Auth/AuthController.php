<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return redirect()->intended(
            $user->isStudent() ? route('student.dashboard') : route('admin.dashboard')
        );
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'consent_data_processing' => ['accepted'],
        ]);

        $hasEmail = !empty(env('MAIL_HOST')) && env('MAIL_HOST') !== '127.0.0.1';
        $hasSms = !empty(env('SMS_PROVIDER_KEY'));

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => UserRole::Student,
            'email_verified_at' => ($hasEmail || $hasSms) ? null : now(),
        ]);

        StudentProfile::create([
            'user_id' => $user->id,
            'consent_data_processing' => true,
            'consent_given_at' => now(),
        ]);

        if ($hasEmail) {
            // In a real app, we would send an email verification link here.
            // e.g. event(new Registered($user));
        } elseif ($hasSms) {
            // In a real app, send an SMS OTP here.
        }

        Auth::login($user);

        return redirect()->route('student.dashboard')
            ->with('success', 'Welcome to OCRS. Complete your application to get started.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}

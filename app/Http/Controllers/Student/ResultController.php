<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    public function index()
    {
        $profile = Auth::user()->studentProfile;
        if (!$profile) {
            return redirect()->route('student.dashboard')->with('error', 'You must complete your profile and registration first.');
        }

        $registration = $profile->registrations()->where('status', 'confirmed')->latest()->first();
        
        if (!$registration) {
            return redirect()->route('student.dashboard')->with('error', 'You must have a confirmed course registration to view your results.');
        }

        $results = $profile
            ->results()
            ->with(['courseUnit', 'semester'])
            ->where('status', 'published')
            ->latest()
            ->get();

        return view('student.results', compact('results'));
    }
}

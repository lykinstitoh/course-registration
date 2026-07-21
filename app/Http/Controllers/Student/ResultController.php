<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    public function index()
    {
        $results = Auth::user()->studentProfile
            ->results()
            ->with(['courseUnit', 'semester'])
            ->where('status', 'published')
            ->latest()
            ->get();

        return view('student.results', compact('results'));
    }
}

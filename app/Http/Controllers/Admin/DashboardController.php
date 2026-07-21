<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Document;
use App\Models\Intake;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\StudentProfile;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'students' => StudentProfile::count(),
            'pending_applications' => Application::where('status', 'submitted')->count(),
            'pending_documents' => Document::where('status', 'pending')->count(),
            'payments_today' => Payment::where('status', 'completed')
                ->whereDate('paid_at', today())
                ->sum('amount'),
            'active_registrations' => Registration::whereIn('status', ['submitted', 'confirmed'])->count(),
        ];

        $recentApplications = Application::with(['studentProfile.user', 'programme'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentApplications'));
    }
}

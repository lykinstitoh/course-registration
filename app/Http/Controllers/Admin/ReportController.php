<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Payment;
use App\Models\Programme;
use App\Models\Registration;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index', [
            'enrollmentByProgramme' => Programme::withCount([
                'applications as approved_count' => fn ($q) => $q->where('status', 'approved'),
            ])->get(),
            'revenueSummary' => Payment::where('status', 'completed')
                ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('method')
                ->get(),
            'registrationStats' => Registration::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ]);
    }
}

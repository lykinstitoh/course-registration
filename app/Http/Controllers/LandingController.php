<?php

namespace App\Http\Controllers;

use App\Models\Intake;
use App\Models\Programme;

class LandingController extends Controller
{
    public function index()
    {
        $programmes = Programme::where('is_active', true)->limit(6)->get();
        $activeIntake = Intake::where('is_active', true)
            ->where('application_closes', '>=', now())
            ->first();

        $institutionName = \App\Models\SystemSetting::where('key', 'institution_name')->value('value') ?? 'OCRS University';

        return view('landing', compact('programmes', 'activeIntake', 'institutionName'));
    }
}

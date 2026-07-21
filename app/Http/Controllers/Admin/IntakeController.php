<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Models\Semester;
use Illuminate\Http\Request;

class IntakeController extends Controller
{
    public function index()
    {
        $intakes = Intake::with('semesters')->latest()->get();

        return view('admin.intakes.index', compact('intakes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'academic_year' => ['required', 'string'],
            'application_opens' => ['required', 'date'],
            'application_closes' => ['required', 'date', 'after:application_opens'],
            'registration_opens' => ['nullable', 'date'],
            'registration_closes' => ['nullable', 'date'],
        ]);

        $intake = Intake::create($data);

        Semester::create([
            'intake_id' => $intake->id,
            'name' => 'Semester 1',
            'sequence' => 1,
            'registration_deadline' => $data['registration_closes'] ?? $data['application_closes'],
            'starts_on' => $data['registration_opens'] ?? $data['application_opens'],
            'ends_on' => $data['registration_closes'] ?? $data['application_closes'],
        ]);

        return back()->with('success', "Intake {$intake->name} created.");
    }
}

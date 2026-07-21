<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\Intake;
use App\Models\Programme;
use Illuminate\Http\Request;

class FeeStructureController extends Controller
{
    public function index()
    {
        $fees = FeeStructure::with(['programme', 'intake'])->latest()->get();
        $programmes = Programme::where('is_active', true)->get();
        $intakes = Intake::where('is_active', true)->get();

        return view('admin.fees.index', compact('fees', 'programmes', 'intakes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'programme_id' => ['required', 'exists:programmes,id'],
            'intake_id' => ['required', 'exists:intakes,id'],
            'fee_type' => ['required', 'string'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_mandatory' => ['boolean'],
        ]);

        FeeStructure::create($data);

        return back()->with('success', 'Fee structure added.');
    }
}

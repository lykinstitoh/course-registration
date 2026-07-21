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
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'award_level' => ['nullable', 'string', 'in:certificate,diploma,degree,masters'],
            'intake_id' => ['required', 'exists:intakes,id'],
            'fee_type' => ['required', 'string'],
            'description' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_mandatory' => ['boolean'],
        ]);

        if (empty($data['programme_id']) && empty($data['award_level'])) {
            return back()->withErrors(['programme_id' => 'You must select either a specific programme or an award level.'])->withInput();
        }

        FeeStructure::updateOrCreate(
            [
                'intake_id' => $data['intake_id'],
                'fee_type' => $data['fee_type'],
                'programme_id' => $data['programme_id'] ?? null,
                'award_level' => $data['award_level'] ?? null,
            ],
            [
                'description' => $data['description'],
                'amount' => $data['amount'],
                'is_mandatory' => $request->has('is_mandatory'),
            ]
        );

        return back()->with('success', 'Fee structure added or updated successfully.');
    }

    public function destroy(FeeStructure $fee)
    {
        $fee->delete();
        return back()->with('success', 'Fee structure deleted successfully.');
    }
}

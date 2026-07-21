<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\Request;

class CampusController extends Controller
{
    public function index()
    {
        $campuses = Campus::latest()->get();
        return view('admin.campuses.index', compact('campuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'unique:campuses,code'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');
        Campus::create($data);

        return back()->with('success', 'Campus created successfully.');
    }

    public function update(Request $request, Campus $campus)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'unique:campuses,code,' . $campus->id],
            'location' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');
        $campus->update($data);

        return back()->with('success', 'Campus updated successfully.');
    }

    public function destroy(Campus $campus)
    {
        if ($campus->applications()->exists()) {
            return back()->with('error', 'Cannot delete campus with existing applications. Consider deactivating it instead.');
        }
        
        $campus->delete();
        return back()->with('success', 'Campus deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::all();
        return response()->json([
            'success' => true,
            'data' => $units
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units,name',
            'abbreviation' => 'required|string|max:10|unique:units,abbreviation',
        ]);

        $unit = Unit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil ditambahkan',
            'data' => $unit
        ]);
    }

    public function update(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units,name,' . $id,
            'abbreviation' => 'required|string|max:10|unique:units,abbreviation,' . $id,
        ]);

        $unit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil diperbarui',
            'data' => $unit
        ]);
    }

    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        
        // Potential check: is unit being used? 
        // For now, simple delete as it is a master data management task.
        
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Satuan berhasil dihapus'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::orderBy('table_number')->get();
        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!PlanService::canAddTable($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota meja Anda sudah penuh. Silakan upgrade plan Anda untuk menambah lebih banyak meja.'
            ], 403);
        }

        $validated = $request->validate([
            'table_number' => [
                'required', 
                'string', 
                Rule::unique('tables')->where('tenant_id', $tenant->id)
            ],
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:available,occupied,reserved',
        ]);

        $table = Table::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil ditambahkan',
            'data' => $table
        ]);
    }

    public function update(Request $request, $id)
    {
        $table = Table::findOrFail($id);

        $tenant = $request->user()->tenant;
        $validated = $request->validate([
            'table_number' => [
                'required', 
                'string', 
                Rule::unique('tables')
                    ->where('tenant_id', $tenant->id)
                    ->ignore($id)
            ],
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:available,occupied,reserved',
        ]);

        $table->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil diperbarui',
            'data' => $table
        ]);
    }

    public function destroy($id)
    {
        $table = Table::findOrFail($id);
        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Meja berhasil dihapus'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:available,occupied,reserved']);
        $table = Table::findOrFail($id);
        $table->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status meja berhasil diupdate',
            'data' => $table
        ]);
    }
}

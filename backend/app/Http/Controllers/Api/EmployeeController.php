<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('position', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $employees = $query->orderBy('name')->get();

        return response()->json(['success' => true, 'data' => $employees]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'position'    => 'nullable|string|max:100',
            'department'  => 'nullable|string|max:100',
            'join_date'   => 'nullable|date',
            'base_salary' => 'nullable|numeric|min:0',
            'status'      => 'nullable|in:active,inactive',
            'notes'       => 'nullable|string',
            'photo'       => 'nullable|image|max:2048',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';
        $validated['base_salary'] = $validated['base_salary'] ?? 0;

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('employees', 'public');
        }

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Karyawan berhasil ditambahkan',
            'data'    => $employee,
        ], 201);
    }

    public function show(string $id)
    {
        $employee = Employee::with(['attendances' => fn($q) => $q->orderBy('date', 'desc')->limit(30)])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $employee]);
    }

    public function update(Request $request, string $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'position'    => 'nullable|string|max:100',
            'department'  => 'nullable|string|max:100',
            'join_date'   => 'nullable|date',
            'base_salary' => 'nullable|numeric|min:0',
            'status'      => 'nullable|in:active,inactive',
            'notes'       => 'nullable|string',
            'photo'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($employee->photo) Storage::disk('public')->delete($employee->photo);
            $validated['photo'] = $request->file('photo')->store('employees', 'public');
        }

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data karyawan berhasil diperbarui',
            'data'    => $employee->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $employee = Employee::findOrFail($id);
        if ($employee->photo) Storage::disk('public')->delete($employee->photo);
        $employee->delete();

        return response()->json(['success' => true, 'message' => 'Karyawan berhasil dihapus']);
    }
}

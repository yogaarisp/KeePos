<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = User::query();

        // Jika superadmin, tampilkan semua user dari semua tenant
        if ($user->role === 'superadmin') {
            $query->with('tenant');
        } else {
            // Jika bukan superadmin, filter berdasarkan tenant_id
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->search . '%')
                  ->orWhere('full_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('full_name')->paginate($request->get('limit', 10));

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;
        if (!PlanService::canAddUser($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'Kuota user Anda sudah penuh. Silakan upgrade plan Anda untuk menambah lebih banyak user.'
            ], 403);
        }

        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'full_name' => 'required|string|max:100',
            'role' => 'required|in:admin,kasir,superadmin',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['tenant_id'] = $request->user()->tenant_id;
        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil ditambahkan',
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $currentUser = $request->user();
        $query = User::query();
        
        if ($currentUser->role !== 'superadmin') {
            $query->where('tenant_id', $currentUser->tenant_id);
        }

        $user = $query->findOrFail($id);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($id)],
            'email' => ['required', 'email', 'max:100', Rule::unique('users')->ignore($id)],
            'full_name' => 'required|string|max:100',
            'role' => 'required|in:admin,kasir,superadmin',
            'is_active' => 'boolean',
            'password' => 'nullable|string|min:6',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui',
            'data' => $user
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();
        $query = User::query();

        if ($currentUser->role !== 'superadmin') {
            $query->where('tenant_id', $currentUser->tenant_id);
        }

        $user = $query->findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun sendiri'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus'
        ]);
    }
}

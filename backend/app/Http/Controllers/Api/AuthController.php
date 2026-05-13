<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        Log::info('Login attempt', ['email' => $request->email, 'ip' => $request->ip()]);
        
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // IMPORTANT: Bypass global scope to find user even if tenant isn't identified yet
        $user = User::withoutGlobalScope('tenant')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Check if tenant is active
        if ($user->tenant && !$user->tenant->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun toko Anda sedang dinonaktifkan. Silakan hubungi admin.'],
            ]);
        }

        // Check if email is verified (only for new/inactive users)
        if (!$user->is_active && !$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email Anda belum diverifikasi.',
                'needs_verification' => true,
                'email' => $user->email,
                'user_id' => $user->id
            ], 403);
        }

        // Final check for inactive accounts (that AR verified but deactivated by admin)
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda sedang dinonaktifkan.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('tenant')
        ]);
    }

    /**
     * Registration is handled by RegisterController@register
     */

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('tenant'));
    }
}

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

        // Final check for inactive accounts (that ARE verified but deactivated by admin)
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda sedang dinonaktifkan.'],
            ]);
        }

        // Handle 2FA check
        if ($user->two_factor_enabled) {
            $otpCode = sprintf("%06d", mt_rand(1, 999999));
            $user->two_factor_code = $otpCode;
            $user->two_factor_expires_at = now()->addMinutes(10);
            $user->save();

            // Send notification (queued)
            $user->notify(new \App\Notifications\TwoFactorOTPNotification($otpCode));

            return response()->json([
                'message' => 'Kode verifikasi dua faktor telah dikirim ke email Anda.',
                'two_factor_required' => true,
                'email' => $user->email
            ], 200);
        }

        // Update last login
        $user->last_login = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('tenant')
        ]);
    }

    /**
     * Verify 2FA code during login
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::withoutGlobalScope('tenant')
            ->where('email', $request->email)
            ->where('two_factor_code', $request->code)
            ->where('two_factor_expires_at', '>', now())
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi 2FA salah atau sudah kadaluarsa.'],
            ]);
        }

        // Clear 2FA data
        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->last_login = now();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('tenant')
        ]);
    }

    /**
     * Toggle 2FA on/off for authenticated user
     */
    public function toggleTwoFactor(Request $request)
    {
        $user = $request->user();
        $user->two_factor_enabled = !$user->two_factor_enabled;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->two_factor_enabled 
                ? 'Autentikasi dua faktor (2FA) berhasil diaktifkan!' 
                : 'Autentikasi dua faktor (2FA) dinonaktifkan.',
            'two_factor_enabled' => $user->two_factor_enabled
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

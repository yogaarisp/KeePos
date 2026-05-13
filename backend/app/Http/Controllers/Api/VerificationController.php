<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OTPVerification;
use App\Notifications\VerifyEmailOTPNotification;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;

class VerificationController extends Controller
{
    /**
     * Verify the user's email address via OTP.
     */
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $otp = OTPVerification::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return response()->json([
                'message' => 'Kode OTP tidak valid atau sudah kadaluarsa.'
            ], 422);
        }

        $user = User::withoutGlobalScope('tenant')->where('email', $request->email)->firstOrFail();

        if ($user->hasVerifiedEmail()) {
            $otp->delete();
            return response()->json(['message' => 'Email sudah diverifikasi sebelumnya.']);
        }

        if ($user->markEmailAsVerified()) {
            $user->is_active = true;
            $user->save();

            // Also activate the tenant
            $tenant = \App\Models\Tenant::find($user->tenant_id);
            if ($tenant) {
                $tenant->is_active = true;
                $tenant->save();
            }

            event(new Verified($user));
        }

        $otp->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil diverifikasi!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('tenant')
        ]);
    }

    /**
     * Verify the user's email address (legacy link support).
     */
    public function verify(Request $request)
    {
        $user = User::withoutGlobalScope('tenant')->findOrFail($request->route('id'));

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
             return response()->json(['message' => 'Link verifikasi tidak valid.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah diverifikasi sebelumnya.']);
        }

        if ($user->markEmailAsVerified()) {
            $user->is_active = true;
            $user->save();

            // Also activate the tenant
            $tenant = \App\Models\Tenant::find($user->tenant_id);
            if ($tenant) {
                $tenant->is_active = true;
                $tenant->save();
            }

            event(new Verified($user));
        }

        return response()->json(['message' => 'Email berhasil diverifikasi!']);
    }

    /**
     * Resend the email verification OTP.
     */
    public function resend(Request $request)
    {
        $email = $request->email ?: $request->user()?->email;
        
        if (!$email) {
            return response()->json(['message' => 'Email required.'], 400);
        }

        $user = User::withoutGlobalScope('tenant')->where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah diverifikasi.'], 400);
        }

        $otpCode = sprintf("%06d", mt_rand(1, 999999));
        OTPVerification::updateOrCreate(
            ['email' => $user->email],
            [
                'code' => $otpCode,
                'expires_at' => now()->addMinutes(10)
            ]
        );

        $user->notify(new VerifyEmailOTPNotification($otpCode));

        return response()->json(['message' => 'Kode OTP baru telah dikirim ke email Anda.']);
    }
}

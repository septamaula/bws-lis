<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthApiController extends Controller
{
    public function demoUsers(): JsonResponse
    {
        $users = User::query()
            ->select('id', 'nik', 'name', 'email', 'role_name', 'department', 'branch')
            ->orderBy('id')
            ->get();

        return response()->json([
            'demoUsers' => $users,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'api-login-attempt:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => "Terlalu banyak percobaan login gagal. Demi keamanan perbankan, silakan tunggu {$seconds} detik.",
                'errors' => [
                    'email' => ["Terlalu banyak percobaan login gagal. Silakan tunggu {$seconds} detik."],
                ],
            ], 429);
        }

        $loginInput = trim($credentials['email']);
        $user = User::with('level')
            ->where('email', $loginInput)
            ->orWhere('nik', $loginInput)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 180);

            AuditLog::record('API_LOGIN_FAILED', null, null, [
                'attempted_input' => $loginInput,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Kredensial NIK/Email atau Password yang dimasukkan tidak sesuai.',
                'errors' => [
                    'email' => ['Kredensial NIK/Email atau Password yang dimasukkan tidak sesuai.'],
                ],
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Akun pengguna dinonaktifkan. Silakan hubungi Administrator Human Capital.',
                'errors' => [
                    'email' => ['Akun pengguna sedang dalam status tidak aktif.'],
                ],
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $user->createToken('bws-lis-token')->plainTextToken;

        AuditLog::record('API_LOGIN_SUCCESS', $user, null, [
            'email' => $user->email,
            'role' => $user->role_name,
            'branch' => $user->branch,
        ]);

        return response()->json([
            'message' => 'Otentikasi perbankan berhasil.',
            'user' => $user->only(['id', 'nik', 'name', 'email', 'role_name', 'department', 'branch', 'level_id']),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()?->only(['id', 'nik', 'name', 'email', 'role_name', 'department', 'branch']),
        ]);
    }

    public function switchRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string'],
        ]);

        $targetUser = User::where('role_name', $validated['role_name'])->firstOrFail();

        if ($request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        $token = $targetUser->createToken('bws-lis-token')->plainTextToken;

        AuditLog::record('SWITCH_ROLE_DEMO', $targetUser, null, [
            'switched_to' => $targetUser->role_name,
            'nik' => $targetUser->nik,
        ]);

        return response()->json([
            'message' => "Role aktif berhasil beralih ke: {$targetUser->role_name}",
            'user' => $targetUser->only(['id', 'nik', 'name', 'email', 'role_name', 'department', 'branch']),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            AuditLog::record('API_LOGOUT', $user);
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'message' => 'Sesi perbankan berhasil diakhiri dengan aman.',
        ]);
    }
}

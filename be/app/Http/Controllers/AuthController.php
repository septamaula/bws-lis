<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        $demoUsers = User::select('id', 'nik', 'name', 'email', 'role_name', 'department', 'branch')
            ->orderBy('id')
            ->get();

        return Inertia::render('Auth/Login', [
            'demoUsers' => $demoUsers,
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = 'login-attempt:'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login gagal. Demi keamanan perbankan, silakan tunggu {$seconds} detik.",
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            AuditLog::record('USER_LOGIN_SUCCESS', $user, null, [
                'email' => $user->email,
                'role' => $user->role_name,
                'branch' => $user->branch,
            ]);

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($throttleKey, 180);

        AuditLog::record('USER_LOGIN_FAILED', null, null, [
            'attempted_email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);

        return back()->withErrors([
            'email' => 'Kredensial NIK/Email atau Password yang dimasukkan tidak sesuai.',
        ]);
    }

    public function switchRole(Request $request)
    {
        $request->validate([
            'role_name' => ['required', 'string'],
        ]);

        $targetUser = User::where('role_name', $request->role_name)->firstOrFail();
        Auth::login($targetUser);
        $request->session()->regenerate();

        AuditLog::record('SWITCH_ROLE_DEMO', $targetUser, null, [
            'switched_to' => $targetUser->role_name,
            'nik' => $targetUser->nik,
        ]);

        return redirect()->route('dashboard')->with('success', "Role aktif berhasil beralih ke: {$targetUser->role_name}");
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::record('USER_LOGOUT', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

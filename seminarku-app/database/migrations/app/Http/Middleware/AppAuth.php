<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Wajib masuk, akun tidak nonaktif, dan (opsional) peran sesuai.
 * Pemakaian di route: ->middleware('app') atau ->middleware('app:peserta,penyelenggara').
 */
class AppAuth
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $u = Auth::user();

        if (!$u || $u->status === 'nonaktif') {
            $nonaktif = $u !== null;
            if ($nonaktif) {
                Auth::logout();
            }
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Silakan masuk terlebih dahulu.'], 401);
            }
            flash($nonaktif ? 'error' : 'warn', $nonaktif ? 'Akun Anda dinonaktifkan. Hubungi administrator.' : 'Silakan masuk terlebih dahulu.');
            return redirect()->guest(route('login'));
        }

        if ($roles && !in_array($u->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Tidak diizinkan.'], 403);
            }
            flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}

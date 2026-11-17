<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Untuk halaman panitia: penyelenggara (sudah divalidasi admin) atau admin.
 * Pemakaian: ->middleware('organizer')
 */
class OrganizerOnly extends AppAuth
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        return parent::handle($request, function ($request) use ($next) {
            $u = $request->user();
            if ($u->role === 'penyelenggara' && $u->status !== 'aktif') {
                flash('warn', 'Akun penyelenggara Anda masih menunggu validasi administrator.');
                return redirect()->route('dashboard');
            }
            return $next($request);
        }, 'penyelenggara', 'admin');
    }
}

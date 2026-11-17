<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $email = strtolower(trim((string)$request->input('email')));
        $u = User::where('email', $email)->first();

        if ($u && Hash::check((string)$request->input('password'), $u->password)) {
            if ($u->status === 'nonaktif') {
                flash('error', 'Akun Anda dinonaktifkan. Hubungi administrator.');
                return redirect()->route('login');
            }
            Auth::login($u);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        flash('error', 'Email atau kata sandi salah.');
        return redirect()->route('login')->withInput($request->only('email'));
    }

    public function showRegister(Request $request)
    {
        $role = in_array($request->query('role'), ['peserta', 'penyelenggara'], true) ? $request->query('role') : 'peserta';
        return view('auth.register', ['role' => old('role', $role)]);
    }

    public function register(Request $request)
    {
        $in = [];
        foreach (['name', 'email', 'role', 'nim', 'institution', 'phone'] as $k) {
            $in[$k] = trim((string)$request->input($k, $k === 'role' ? 'peserta' : ''));
        }
        $pw = (string)$request->input('password', '');

        $errors = [];
        if (mb_strlen($in['name']) < 3) $errors[] = 'Nama lengkap minimal 3 karakter.';
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
        if (!in_array($in['role'], ['peserta', 'penyelenggara'], true)) $errors[] = 'Peran tidak valid.';
        if (strlen($pw) < 8) $errors[] = 'Kata sandi minimal 8 karakter.';
        if ($pw !== (string)$request->input('password2', '')) $errors[] = 'Konfirmasi kata sandi tidak sama.';
        if ($in['role'] === 'peserta' && $in['nim'] === '') $errors[] = 'NIM wajib diisi untuk peserta.';
        if (!$request->boolean('agree')) $errors[] = 'Setujui ketentuan layanan untuk melanjutkan.';
        if (!$errors && User::where('email', strtolower($in['email']))->exists()) {
            $errors[] = 'Email sudah terdaftar.';
        }

        if ($errors) {
            foreach ($errors as $m) {
                flash('error', $m);
            }
            return redirect()->route('register')->withInput($request->except('password', 'password2'));
        }

        $status = $in['role'] === 'penyelenggara' ? 'menunggu' : 'aktif';
        $u = User::create([
            'name' => $in['name'],
            'email' => strtolower($in['email']),
            'password' => $pw,                       // di-hash otomatis oleh cast pada model User
            'role' => $in['role'],
            'nim' => $in['nim'] ?: null,
            'institution' => $in['institution'] ?: null,
            'phone' => $in['phone'] ?: null,
            'status' => $status,
        ]);
        Auth::login($u);
        $request->session()->regenerate();
        flash($status === 'aktif' ? 'ok' : 'info', $status === 'aktif'
            ? 'Akun berhasil dibuat. Selamat datang!'
            : 'Akun dibuat. Anda dapat membuat acara setelah administrator memvalidasi akun ini.');
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}

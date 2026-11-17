<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;

/** Pengaturan akun: ubah profil dan ganti kata sandi (semua peran). */
class ProfileController extends Controller
{
    public function show()
    {
        return view('pages.profile', ['u' => user()]);
    }

    public function update()
    {
        $r = request();
        $u = user();
        $action = trim((string)$r->input('action'));

        if ($action === 'profil') {
            $name = trim((string)$r->input('name'));
            if (mb_strlen($name) < 3) {
                flash('error', 'Nama lengkap minimal 3 karakter.');
            } else {
                $u->update([
                    'name' => $name,
                    'nim' => trim((string)$r->input('nim')) ?: null,
                    'institution' => trim((string)$r->input('institution')) ?: null,
                    'phone' => trim((string)$r->input('phone')) ?: null,
                ]);
                flash('ok', 'Profil diperbarui. Sertifikat yang terbit berikutnya akan memakai nama ini.');
            }
        } elseif ($action === 'sandi') {
            $new = (string)$r->input('new', '');
            if (!Hash::check((string)$r->input('old', ''), $u->password)) {
                flash('error', 'Kata sandi lama salah.');
            } elseif (strlen($new) < 8) {
                flash('error', 'Kata sandi baru minimal 8 karakter.');
            } elseif ($new !== (string)$r->input('new2', '')) {
                flash('error', 'Konfirmasi kata sandi tidak sama.');
            } else {
                $u->update(['password' => $new]);       // di-hash otomatis oleh cast pada model User
                flash('ok', 'Kata sandi diganti.');
            }
        }

        return redirect()->route('profile');
    }
}

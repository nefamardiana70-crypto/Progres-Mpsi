@extends('layouts.auth', ['title' => 'Masuk', 'artTitle' => 'Seminar kampus dalam satu tempat', 'artText' => 'Cari acara, daftar, dan simpan sertifikat Anda.'])

@section('panel')
            <h1>Masuk</h1>
            <p class="sub">Gunakan email yang Anda daftarkan.</p>
            <form method="post" action="<?= e(route('login.post')) ?>" class="stack">
                <?= csrf_field() ?>
                <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" required autofocus autocomplete="username" placeholder="nama@email.com" value="<?= e(old('email')) ?>"></div>
                <div class="field"><label for="password">Kata sandi</label><input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Kata sandi"></div>
                <button class="btn btn-block" type="submit">Masuk</button>
            </form>
            <p class="foot">Belum punya akun? <a class="link" href="<?= e(route('register')) ?>">Daftar</a></p>
            <div class="demo-box">
                <b>Akun contoh</b><br>
                Peserta: mhs@seminar.test / mhs12345<br>
                Panitia: panitia@seminar.test / panitia123<br>
                Admin: admin@seminar.test / admin123
            </div>
            <p class="foot"><a class="link" href="<?= e(route('ketentuan')) ?>">Ketentuan layanan</a> · <a class="link" href="<?= e(route('home')) ?>">Beranda</a></p>
@endsection

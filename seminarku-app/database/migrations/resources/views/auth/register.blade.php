@extends('layouts.auth', ['title' => 'Daftar', 'artTitle' => 'Buat akun SeminarKu', 'artText' => 'Peserta langsung bisa mendaftar acara. Panitia divalidasi admin lebih dulu.'])

@section('panel')
            <h1>Daftar</h1>
            <p class="sub">Pilih peran, lalu lengkapi data Anda.</p>
            <form method="post" action="<?= e(route('register.post')) ?>" class="stack">
                <?= csrf_field() ?>
                <div class="checks">
                    <label><input type="radio" name="role" value="peserta" <?= $role === 'peserta' ? 'checked' : '' ?>> Peserta</label>
                    <label><input type="radio" name="role" value="penyelenggara" <?= $role === 'penyelenggara' ? 'checked' : '' ?>> Panitia</label>
                </div>
                <div class="field"><label for="name">Nama lengkap</label><input id="name" name="name" type="text" required value="<?= e(old('name')) ?>" autocomplete="name">
                    <div class="hint">Dicetak pada sertifikat. Tuliskan beserta gelar bila perlu.</div></div>
                <div class="grid2">
                    <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e(old('email')) ?>" autocomplete="email"></div>
                    <div class="field"><label for="nim">NIM (peserta)</label><input id="nim" name="nim" type="text" value="<?= e(old('nim')) ?>"></div>
                </div>
                <div class="grid2">
                    <div class="field"><label for="institution">Prodi / organisasi</label><input id="institution" name="institution" type="text" value="<?= e(old('institution')) ?>"></div>
                    <div class="field"><label for="phone">No. telepon</label><input id="phone" name="phone" type="text" value="<?= e(old('phone')) ?>" autocomplete="tel"></div>
                </div>
                <div class="grid2">
                    <div class="field"><label for="password">Kata sandi</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
                    <div class="field"><label for="password2">Ulangi kata sandi</label><input id="password2" name="password2" type="password" required minlength="8" autocomplete="new-password"></div>
                </div>
                <div class="checks"><label><input type="checkbox" name="agree" value="1"> <span>Saya menyetujui <a class="link" href="<?= e(route('ketentuan')) ?>" target="_blank" rel="noopener">ketentuan layanan</a></span></label></div>
                <button class="btn btn-block" type="submit">Buat akun</button>
            </form>
            <p class="foot">Sudah punya akun? <a class="link" href="<?= e(route('login')) ?>">Masuk</a></p>
@endsection

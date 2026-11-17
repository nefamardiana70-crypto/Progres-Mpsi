@extends('layouts.app', ['title' => 'Pengaturan Akun', 'active' => 'profile'])

@section('content')
<div class="panel">
    <h2>Profil</h2>
    <p class="sub">Peran: <?= e(role_label($u['role'])) ?> · <?= e($u['email']) ?></p>
    <form method="post" action="<?= e(route('profile.update')) ?>" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="profil">
        <div class="field"><label for="name">Nama lengkap (beserta gelar)</label><input id="name" name="name" type="text" required value="<?= e($u['name']) ?>">
            <div class="hint">Nama ini dicetak pada sertifikat. Pastikan ejaan dan gelar sudah benar sebelum menghadiri acara.</div></div>
        <div class="grid2">
            <div class="field"><label for="nim">NIM</label><input id="nim" name="nim" type="text" value="<?= e((string)$u['nim']) ?>"></div>
            <div class="field"><label for="phone">No. telepon</label><input id="phone" name="phone" type="text" value="<?= e((string)$u['phone']) ?>"></div>
        </div>
        <div class="field"><label for="institution">Institusi / organisasi</label><input id="institution" name="institution" type="text" value="<?= e((string)$u['institution']) ?>"></div>
        <div><button class="btn" type="submit">Simpan profil</button></div>
    </form>
</div>
<div class="panel">
    <h2>Ganti kata sandi</h2>
    <form method="post" action="<?= e(route('profile.update')) ?>" class="stack" style="margin-top:14px"><?= csrf_field() ?><input type="hidden" name="action" value="sandi">
        <div class="field"><label for="old">Kata sandi lama</label><input id="old" name="old" type="password" required></div>
        <div class="grid2">
            <div class="field"><label for="new">Kata sandi baru</label><input id="new" name="new" type="password" required minlength="8"></div>
            <div class="field"><label for="new2">Ulangi kata sandi baru</label><input id="new2" name="new2" type="password" required minlength="8"></div>
        </div>
        <div><button class="btn btn-light" type="submit">Ganti kata sandi</button></div>
    </form>
</div>
@endsection

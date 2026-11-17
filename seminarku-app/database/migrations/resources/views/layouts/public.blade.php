<?php
/* Kerangka halaman publik (sebelum masuk): beranda, verifikasi, ketentuan. Variabel: $title, $class (opsional). */
$u = user();
$flashes = take_flashes();
?>
@include('partials.head')
<body class="pub <?= e($class ?? '') ?>">
<header class="pub-head">
    <a class="pub-brand" href="<?= e(route('home')) ?>"><?= brand_mark(38) ?><span><?= APP_NAME ?></span></a>
    <nav class="pub-nav" aria-label="Menu">
        <a href="<?= e(route('home')) ?>#acara">Acara</a>
        <a href="<?= e(route('verify')) ?>">Verifikasi</a>
        <a href="<?= e(route('ketentuan')) ?>">Ketentuan</a>
    </nav>
    <div class="pub-actions">
        <?= theme_toggle() ?>
        <?php if ($u): ?>
            <a class="btn btn-sm" href="<?= e(route('dashboard')) ?>">Ke beranda</a>
        <?php else: ?>
            <a class="btn btn-light btn-sm" href="<?= e(route('login')) ?>">Masuk</a>
            <a class="btn btn-sm hide-sm" href="<?= e(route('register')) ?>">Daftar</a>
        <?php endif; ?>
    </div>
</header>
<main class="pub-main">
<?php foreach ($flashes as [$type, $msg]): ?>
    <div class="alert alert-<?= e($type) ?> pub-alert" role="status"><?= e($msg) ?></div>
<?php endforeach; ?>
@yield('content')
</main>
<footer class="pub-foot">
    <span>© <?= date('Y') ?> <?= APP_NAME ?> · Fakultas Sains dan Teknologi, UIN Imam Bonjol Padang</span>
    <span><a href="<?= e(route('ketentuan')) ?>">Ketentuan layanan</a> · <a href="<?= e(route('verify')) ?>">Verifikasi sertifikat</a></span>
</footer>
@include('partials.foot')

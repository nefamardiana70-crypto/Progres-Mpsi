<?php
/* Halaman masuk/daftar. Variabel: $title, $artTitle, $artText. Isi panel kanan di @section('panel'). */
$flashes = take_flashes();
?>
@include('partials.head')
<body class="auth-page">
<div class="auth">
    <div class="auth-top"><?= theme_toggle() ?></div>
    <div class="auth-card">
        <?= auth_art($artTitle, $artText) ?>
        <div class="auth-panel">
<?php foreach ($flashes as [$type, $msg]): ?>
            <div class="alert alert-<?= e($type) ?>" role="status"><?= e($msg) ?></div>
<?php endforeach; ?>
@yield('panel')
        </div>
    </div>
</div>
@include('partials.foot')

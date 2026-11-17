<?php
/* Kerangka aplikasi (setelah masuk). Variabel dari @extends: $title, $active, $sub (opsional). */
$u = user();
$active = $active ?? '';
$flashes = take_flashes();
$hasChat = $u['role'] !== 'admin';          // chat bersifat privat: hanya peserta dan panitia acara
$unread = $hasChat ? unread_messages((int)$u['id']) : 0;
$notifs = notif_items($u);
?>
@include('partials.head')
<body class="app" <?= $hasChat ? 'data-chat="1" data-unread-url="' . e(route('chat.api', ['action' => 'unread'])) . '"' : '' ?>>
<div class="shell">
    <aside class="rail">
        <a class="rail-brand" href="<?= e(route('dashboard')) ?>" title="<?= APP_NAME ?>"><?= brand_mark(42) ?></a>
        <nav class="rail-nav" aria-label="Menu utama">
            <?php foreach (nav_items($u['role']) as [$key, $label, $href, $ic]): ?>
                <a href="<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>>
                    <span class="ri"><?= icon($ic, 21) ?><?php if ($key === 'chat'): ?><em class="nbadge js-chat-badge" <?= $unread ? '' : 'hidden' ?>><?= $unread ?></em><?php endif; ?></span>
                    <span class="rl"><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <nav class="rail-nav rail-bottom" aria-label="Akun">
            <a href="<?= e(route('profile')) ?>" class="<?= $active === 'profile' ? 'active' : '' ?>"><span class="ri"><?= icon('settings', 21) ?></span><span class="rl">Akun</span></a>
            <a href="<?= e(route('logout')) ?>"><span class="ri"><?= icon('logout', 21) ?></span><span class="rl">Keluar</span></a>
        </nav>
    </aside>

    <div class="stage">
        <header class="topbar">
            <div class="tb-title">
                <h1><?= e($title) ?></h1>
                <?php if (!empty($sub)): ?><p><?= e($sub) ?></p><?php endif; ?>
            </div>
            <div class="tb-actions">
                <?= theme_toggle() ?>
                <?php if ($hasChat): ?>
                    <a class="icon-btn" href="<?= e(route('chat.index')) ?>" title="Pesan" aria-label="Pesan"><?= icon('chat', 19) ?><em class="nbadge js-chat-badge" <?= $unread ? '' : 'hidden' ?>><?= $unread ?></em></a>
                <?php endif; ?>
                <details class="menu">
                    <summary class="icon-btn" title="Pemberitahuan" aria-label="Pemberitahuan"><?= icon('bell', 19) ?><?= $notifs ? '<i class="dot-alert"></i>' : '' ?></summary>
                    <div class="menu-pop">
                        <div class="menu-head">Pemberitahuan</div>
                        <?php foreach (array_slice($notifs, 0, 6) as [$txt, $href]): ?>
                            <a class="menu-item" href="<?= e($href) ?>"><?= e(mb_strimwidth($txt, 0, 70, '…')) ?></a>
                        <?php endforeach; ?>
                        <?php if (!$notifs): ?><div class="menu-empty">Tidak ada pemberitahuan baru.</div><?php endif; ?>
                    </div>
                </details>
                <details class="menu">
                    <summary class="avatar" title="Akun saya" aria-label="Menu akun"><?= e(initials($u['name'])) ?></summary>
                    <div class="menu-pop menu-right">
                        <div class="menu-user"><b><?= e($u['name']) ?></b><small><?= e(role_label($u['role'])) ?><?= $u['nim'] ? ' · ' . e($u['nim']) : '' ?></small></div>
                        <a class="menu-item" href="<?= e(route('profile')) ?>">Profil &amp; kata sandi</a>
                        <a class="menu-item" href="<?= e(route('verify')) ?>">Verifikasi sertifikat</a>
                        <a class="menu-item" href="<?= e(route('ketentuan')) ?>">Ketentuan layanan</a>
                        <a class="menu-item" href="<?= e(route('logout')) ?>">Keluar</a>
                    </div>
                </details>
            </div>
        </header>
        <main class="content">
<?php foreach ($flashes as [$type, $msg]): ?>
            <div class="alert alert-<?= e($type) ?>" role="status"><?= e($msg) ?></div>
<?php endforeach; ?>
@yield('content')
        </main>
    </div>
</div>
@include('partials.foot')

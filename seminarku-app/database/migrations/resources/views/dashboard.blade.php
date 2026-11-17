@extends('layouts.app', ['title' => 'Beranda', 'active' => 'dashboard'])

@section('content')
<?php if ($pendingOrganizer): ?>
<div class="alert alert-warn">Akun penyelenggara Anda masih <b>menunggu validasi administrator</b>. Setelah divalidasi, Anda dapat membuat dan menerbitkan acara. Sementara itu Anda tetap bisa melihat acara lain lewat menu Jelajah.</div>
<?php else: ?>
<div class="hero-bar">
    <div class="hb-left">
        <div>
            <h1 class="pg-title"><?= $role === 'peserta' ? 'Kalender seminar' : ($role === 'admin' ? 'Ringkasan acara' : 'Kalender acara') ?></h1>
            <p class="pg-sub"><?= e($sub) ?></p>
        </div>
    </div>
    <div class="hb-stats">
        <?php foreach ($stats as $i => [$v, $l]): ?>
            <?php if ($i): ?><span class="hb-sep"></span><?php endif; ?>
            <div class="hb-stat"><small><?= e($l) ?></small><b><?= (int)$v ?></b></div>
        <?php endforeach; ?>
        <a class="btn" href="<?= e($cta[0]) ?>"><?= icon($cta[2], 16) ?> <?= e($cta[1]) ?></a>
    </div>
</div>

<div class="cal-wrap">
    <aside class="cal-side">
        <div class="side-card">
            <div class="mini-head">
                <h3><?= e(BULAN[(int)date('n', $pickTs)] . ' ' . date('Y', $pickTs)) ?></h3>
                <div>
                    <a href="<?= e($link($view, date('Y-m-d', strtotime('-1 month', strtotime($mFirst))))) ?>" aria-label="Bulan sebelumnya"><?= icon('left', 14) ?></a>
                    <a href="<?= e($link($view, date('Y-m-d', strtotime('+1 month', strtotime($mFirst))))) ?>" aria-label="Bulan berikutnya"><?= icon('right', 14) ?></a>
                </div>
            </div>
            <div class="mini-grid">
                <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h): ?><span class="dow"><?= $h ?></span><?php endforeach; ?>
                <?php for ($t = strtotime($mStart); $t <= strtotime($mEnd); $t = strtotime('+1 day', $t)):
                    $ds = date('Y-m-d', $t);
                    $cls = [];
                    if (date('Y-m', $t) !== date('Y-m', $pickTs)) $cls[] = 'out';
                    if ($ds === $today) $cls[] = 'today';
                    if ($ds === $pick) $cls[] = 'pick';
                    if (isset($hasEv[$ds])) $cls[] = 'has';
                    if ($rangeFrom !== '' && $ds >= $rangeFrom && $ds <= $rangeTo && $view === 'week') {
                        $cls[] = 'inr';
                        if ($ds === $rangeFrom) $cls[] = 'first';
                        if ($ds === $rangeTo) $cls[] = 'last';
                    }
                    ?>
                    <a class="<?= e(implode(' ', $cls)) ?>" href="<?= e($link($view === 'month' ? 'day' : $view, $ds)) ?>"><?= (int)date('j', $t) ?></a>
                <?php endfor; ?>
            </div>
        </div>

        <div class="side-card">
            <h3><?= e($agendaTitle) ?> <a href="<?= e($agendaAll) ?>">Lihat semua</a></h3>
            <ul class="agenda">
                <?php foreach ($ag as [$href, $ttl, $meta, $done]): ?>
                    <li class="<?= $done ? 'done' : '' ?>"><a href="<?= e($href) ?>">
                        <span class="ck"><?= $done ? icon('check', 11) : '' ?></span>
                        <span class="tx"><?= e($ttl) ?><small><?= e($meta) ?></small></span></a></li>
                <?php endforeach; ?>
                <?php if (!$ag): ?><li class="none"><?= $role === 'peserta' ? 'Belum ada pendaftaran. Mulai dari menu Cari.' : 'Belum ada acara mendatang.' ?></li><?php endif; ?>
            </ul>
        </div>
    </aside>

    <section class="wk" aria-label="Kalender">
        <div class="wk-bar">
            <h2><?= e($calTitle) ?></h2>
            <div class="wk-nav">
                <div class="seg" role="group" aria-label="Tampilan">
                    <a class="<?= $view === 'day' ? 'on' : '' ?>" href="<?= e($link('day', $pick)) ?>">Harian</a>
                    <a class="<?= $view === 'week' ? 'on' : '' ?>" href="<?= e($link('week', $pick)) ?>">Mingguan</a>
                    <a class="<?= $view === 'month' ? 'on' : '' ?>" href="<?= e($link('month', $pick)) ?>">Bulanan</a>
                </div>
                <a href="<?= e($link($view, $prev)) ?>" aria-label="Sebelumnya"><?= icon('left', 14) ?></a>
                <a href="<?= e($link($view, $today)) ?>">Hari ini</a>
                <a href="<?= e($link($view, $next)) ?>" aria-label="Berikutnya"><?= icon('right', 14) ?></a>
            </div>
        </div>
        <div class="legend">
            <?php if ($role === 'peserta'): ?>
                <span><i class="lg-mine"></i>Tiket saya</span><span><i class="lg-wait"></i>Menunggu persetujuan</span><span><i class="lg-open"></i>Masih dibuka</span>
            <?php elseif ($role === 'penyelenggara'): ?>
                <span><i class="lg-mine"></i>Acara saya</span><span><i class="lg-open"></i>Acara panitia lain</span><span><i class="lg-draft"></i>Draf</span>
            <?php else: ?>
                <span><i class="lg-mine"></i>Terbit</span><span><i class="lg-draft"></i>Draf</span>
            <?php endif; ?>
            <span><i class="lg-done"></i>Sudah lewat</span>
        </div>

<?php if ($view === 'month'): ?>
        <div class="mo">
            <?php foreach (['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MIN'] as $h): ?><div class="dh"><?= $h ?></div><?php endforeach; ?>
            <?php for ($t = strtotime($from); $t <= strtotime($to); $t = strtotime('+1 day', $t)):
                $ds = date('Y-m-d', $t);
                $list = $byDay[$ds] ?? [];
                ?>
                <div class="cell<?= date('Y-m', $t) !== date('Y-m', $pickTs) ? ' out' : '' ?><?= $ds === $today ? ' today' : '' ?>">
                    <a class="n" href="<?= e($link('day', $ds)) ?>"><?= (int)date('j', $t) ?></a>
                    <?php foreach (array_slice($list, 0, 3) as $ev): [$cls, $href] = $classify($ev); ?>
                        <a class="mo-ev <?= $cls ?>" href="<?= e($href) ?>" title="<?= e($ev['title']) ?>"><?= e($ev['start_time']) ?> <?= e($ev['title']) ?></a>
                    <?php endforeach; ?>
                    <?php if (count($list) > 3): ?><a class="mo-more" href="<?= e($link('day', $ds)) ?>">+<?= count($list) - 3 ?> lagi</a><?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
<?php else:
    $startH = 7; $endH = 22; $hours = $endH - $startH; $rowh = 52;
    $days = [];
    for ($t = strtotime($from); $t <= strtotime($to); $t = strtotime('+1 day', $t)) {
        $days[] = date('Y-m-d', $t);
    }
    ?>
        <div class="wk-head" style="--cols:<?= count($days) ?>">
            <div></div>
            <?php foreach ($days as $ds): $t = strtotime($ds); ?>
                <div class="hd<?= $ds === $today ? ' today' : '' ?><?= $ds === $pick ? ' picked' : '' ?>">
                    <small><?= e(mb_strtoupper(mb_substr(HARI[(int)date('N', $t)], 0, 3))) ?></small>
                    <a href="<?= e($link('day', $ds)) ?>"><b><?= (int)date('j', $t) ?></b></a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="wk-scroll" data-start="<?= $startH ?>" style="--rowh:<?= $rowh ?>px">
            <div class="wk-body" style="--cols:<?= count($days) ?>; --hours:<?= $hours ?>; --rowh:<?= $rowh ?>px">
                <div class="wk-time">
                    <?php for ($h = $startH; $h < $endH; $h++): ?><div><?= sprintf('%02d.00', $h) ?></div><?php endfor; ?>
                </div>
                <?php foreach ($days as $ds):
                    $list = $byDay[$ds] ?? [];
                    // Bagi lajur untuk acara yang bertumpuk (durasi 2 jam)
                    $lanes = [];
                    $place = [];
                    foreach ($list as $k => $ev) {
                        [$hh, $mm] = array_map('intval', explode(':', $ev['start_time']));
                        $s0 = max($startH * 60, min($endH * 60 - 30, $hh * 60 + $mm));
                        $e0 = min($endH * 60, $s0 + 120);
                        $lane = 0;
                        while (isset($lanes[$lane]) && $lanes[$lane] > $s0) $lane++;
                        $lanes[$lane] = $e0;
                        $place[$k] = [$s0, $e0, $lane];
                    }
                    $nl = max(1, count($lanes));
                    ?>
                    <div class="wk-col<?= $ds === $today ? ' today' : '' ?>">
                        <?php foreach ($list as $k => $ev):
                            [$cls, $href] = $classify($ev);
                            [$s0, $e0, $lane] = $place[$k];
                            $top = ($s0 - $startH * 60) / 60 * $rowh;
                            $ht = max(40, ($e0 - $s0) / 60 * $rowh - 3);
                            $w = 100 / $nl;
                            ?>
                            <a class="wk-ev <?= $cls ?>" href="<?= e($href) ?>" title="<?= e($ev['title']) ?>"
                               style="top:<?= round($top, 1) ?>px;height:<?= round($ht, 1) ?>px;left:calc(<?= round($lane * $w, 2) ?>% + 3px);width:calc(<?= round($w, 2) ?>% - 6px)">
                                <b><?= e($ev['title']) ?></b>
                                <span><?= e($ev['start_time']) ?> · <?= e(ucfirst($ev['mode'])) ?></span>
                                <?php if ($role !== 'peserta'): ?><span><?= e($ev['organizer_name']) ?></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (in_array($today, $days, true) && (int)date('G') >= $startH && (int)date('G') < $endH):
                    $nowTop = ((int)date('G') * 60 + (int)date('i') - $startH * 60) / 60 * $rowh; ?>
                    <div class="wk-now" style="top:<?= round($nowTop, 1) ?>px"></div>
                <?php endif; ?>
            </div>
        </div>
<?php endif; ?>
    </section>
</div>
<?php endif; ?>
@endsection

@extends('layouts.app', ['title' => $u['role'] === 'admin' ? 'Semua Acara' : 'Cari Seminar', 'active' => 'events'])

@section('content')
<form class="filters" method="get" action="<?= e(route('events.index')) ?>">
    <div class="search-pill"><label class="sr" for="q">Cari</label><input id="q" type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul, lokasi, atau penyelenggara…"></div>
    <button class="btn btn-sm" type="submit">Cari</button>
</form>
<form class="filters" method="get" action="<?= e(route('events.index')) ?>">
    <input type="hidden" name="q" value="<?= e($q) ?>">
    <?php if ($date): ?><input type="hidden" name="date" value="<?= e($date) ?>"><?php endif; ?>
    <span class="lbl">Filter:</span>
    <?= filter_select('kategori', 'Kategori', array_combine(CATEGORIES, CATEGORIES), $cat) ?>
    <?= filter_select('waktu', 'Tanggal', ['hari' => 'Hari ini', 'minggu' => '7 hari ke depan', 'bulan' => 'Bulan ini'], $when) ?>
    <?= filter_select('mode', 'Mode', ['online' => 'Online', 'offline' => 'Offline'], $mode) ?>
    <?= filter_select('biaya', 'Biaya', ['gratis' => 'Gratis', 'berbayar' => 'Berbayar'], $fee) ?>
    <?= filter_select('kuota', 'Kuota', ['tersedia' => 'Masih tersedia'], $quota) ?>
    <?= filter_select('sertifikat', 'Sertifikat', ['1' => 'Menerbitkan sertifikat'], $cert) ?>
    <?php if ($filtered): ?><a class="btn-reset" href="<?= e(route('events.index')) ?>">Reset</a><?php endif; ?>
    <noscript><button class="btn btn-sm">Terapkan</button></noscript>
</form>
<?php if ($date): ?>
    <div class="alert alert-info">Menampilkan acara pada <b><?= e(tgl($date, true)) ?></b>.</div>
<?php endif; ?>
<p class="muted" style="margin:0 0 14px;font-size:12.5px"><?= count($rows) ?> acara <?= $date ? 'ditemukan' : 'sedang dibuka' ?></p>
<div class="seg" style="margin:0 0 16px" role="group" aria-label="Tampilan daftar">
    <a class="<?= $grid ? '' : 'on' ?>" href="<?= e($tv('daftar')) ?>">Daftar</a>
    <a class="<?= $grid ? 'on' : '' ?>" href="<?= e($tv('galeri')) ?>">Galeri poster</a>
</div>
<?php if (!count($rows)): ?>
    <div class="empty"><b>Tidak ada acara yang cocok</b>Coba ubah kata kunci atau longgarkan filter.</div>
<?php elseif ($grid): ?>
    <div class="feed">
    <?php foreach ($rows as $r): $left = seats_left($r); $t = strtotime((string)$r['event_date']); ?>
        <a class="post" href="<?= e(route('events.show', $r['id'])) ?>">
            <span class="post-img"><?php if (!empty($r['poster'])): ?><img loading="lazy" src="<?= e(upload_url('poster', $r['id'])) ?>" alt="Poster <?= e($r['title']) ?>"><?php else: ?><span class="post-none"><b><?= date('j', $t) ?></b><?= e(BULAN[(int)date('n', $t)]) ?></span><?php endif; ?>
                <?php if ($left <= 0): ?><em class="post-tag">Penuh</em><?php elseif ((int)$r['fee'] > 0): ?><em class="post-tag">Berbayar</em><?php endif; ?></span>
            <span class="post-body"><b><?= e($r['title']) ?></b>
                <small><?= e(tgl($r['event_date'])) ?> · <?= e($r['start_time']) ?> WIB · <?= e(ucfirst($r['mode'])) ?></small>
                <small><?= e($r['organizer_name']) ?></small></span>
        </a>
    <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="course-list">
    <?php foreach ($rows as $i => $r):
        $left = seats_left($r);
        $badge = $left <= 0 ? '<span class="badge badge-err">Penuh</span>'
            : ($left <= 5 ? '<span class="badge badge-warn">Sisa ' . $left . ' kursi</span>' : '<span class="badge">' . $left . ' kursi</span>');
        if ((int)$r['fee'] > 0) $badge .= '<span class="badge badge-info">Berbayar</span>';
        if ($r['has_certificate']) $badge .= '<span class="badge badge-ok">Sertifikat</span>';
        echo event_card($r, route('events.show', $r['id']), $i, $badge);
    endforeach; ?>
    </div>
<?php endif; ?>
@endsection

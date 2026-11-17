@extends('layouts.app', ['title' => 'Detail acara', 'active' => $active])

@section('content')
<div class="hero-event tone-<?= $tone ?>">
    <div class="edate"><b><?= date('j', $t) ?></b><span><?= e(mb_strtoupper(mb_substr(BULAN[(int)date('n', $t)], 0, 3))) ?></span><i><?= date('Y', $t) ?></i></div>
    <div class="he-body">
        <div class="badges">
            <?= badge($ev['category'], 'badge-info') ?>
            <?= $paid ? badge('Berbayar', 'badge-warn') : badge('Gratis', 'badge-ok') ?>
            <?php if ($ev['has_certificate']): ?><?= badge('E-sertifikat', 'badge-ok') ?><?php endif; ?>
            <?php if ($ev['status'] === 'draft'): ?><?= badge('Draf (belum tampil)', 'badge-gray') ?><?php elseif ($ev['status'] === 'ditutup'): ?><?= badge('Ditutup', 'badge-err') ?><?php elseif (event_done($ev)): ?><?= badge('Sudah terlaksana', 'badge-gray') ?><?php endif; ?>
        </div>
        <h2><?= e($ev['title']) ?></h2>
        <div class="muted">Diselenggarakan oleh <b style="color:var(--ink-2)"><?= e($ev['organizer_name']) ?></b></div>
    </div>
</div>

<div class="detail">
    <div>
        <?php if (!empty($ev['poster'])): ?>
        <a class="poster-full" href="<?= e(upload_url('poster', $ev['id'])) ?>" target="_blank" rel="noopener" title="Buka poster ukuran penuh">
            <img src="<?= e(upload_url('poster', $ev['id'])) ?>" alt="Poster <?= e($ev['title']) ?>"></a>
        <?php endif; ?>
        <div class="panel">
            <h2>Tentang acara</h2>
            <p style="margin:8px 0 0;white-space:pre-line;color:var(--ink-2)"><?= e($ev['description']) ?></p>
        </div>
        <div class="panel">
            <h2>Informasi pelaksanaan</h2>
            <ul class="facts" style="margin-top:14px">
                <li><?= icon('calendar', 18) ?><span><?= e(tgl($ev['event_date'], true)) ?><small>Pukul <?= e($ev['start_time']) ?> WIB</small></span></li>
                <li><?= icon('pin', 18) ?><span><?= e($ev['location']) ?><small>Mode <?= e($ev['mode']) ?></small></span></li>
                <li><?= icon('wallet', 18) ?><span><?= e(rupiah((int)$ev['fee'])) ?><small><?= $paid ? 'Bayar lewat QRIS atau rekening penyelenggara, lalu unggah bukti' : 'Tanpa biaya pendaftaran' ?></small></span></li>
                <li><?= icon('shield', 18) ?><span><?= ($ev['approval_mode'] === 'auto' && !$paid) ? 'Disetujui otomatis' : 'Disetujui panitia' ?><small>QR tiket muncul setelah pendaftaran disetujui</small></span></li>
                <li><?= icon('award', 18) ?><span><?= $ev['has_certificate'] ? 'Menerbitkan e-sertifikat' : 'Tanpa sertifikat' ?><small><?= $ev['has_certificate'] ? 'Terbit otomatis setelah kehadiran tercatat' : '' ?></small></span></li>
                <?php if ($ev['reg_deadline']): ?><li><?= icon('clock', 18) ?><span>Pendaftaran sampai <?= e(tgl($ev['reg_deadline'], true)) ?></span></li><?php endif; ?>
            </ul>
        </div>
    </div>

    <div>
        <div class="panel">
            <h3>Kuota</h3>
            <div class="meter"><i style="width:<?= $pct ?>%"></i></div>
            <p class="muted" style="margin:8px 0 14px;font-size:12px"><?= (int)$ev['registered'] ?> dari <?= (int)$ev['quota'] ?> kursi terisi · <b style="color:var(--ink)"><?= $left ?></b> tersisa</p>

            <?php if ($u['role'] === 'peserta'): ?>
                <?php if ($myReg): [$lbl, $cls] = reg_state($myReg, $ev); ?>
                    <p style="margin:0 0 12px"><?= badge($lbl, $cls) ?></p>
                    <a class="btn btn-block" href="<?= e(route('tickets.show', $myReg['id'])) ?>"><?= icon('ticket', 16) ?> Buka tiket</a>
                <?php elseif ($open): ?>
                    <?php if ($clash): ?>
                        <div class="alert alert-warn" style="font-size:12px">Bertabrakan dengan acara Anda pada tanggal yang sama:
                            <?php foreach ($clash as $c): ?><br>• <?= e($c['title']) ?> (<?= e($c['start_time']) ?>)<?php endforeach; ?></div>
                    <?php endif; ?>
                    <?php if ($paid): ?><div class="pay-amount" style="margin-bottom:10px"><?= e(rupiah((int)$ev['fee'])) ?></div><?php endif; ?>
                    <form method="post" action="<?= e(route('events.register', $ev['id'])) ?>"><?= csrf_field() ?>
                        <button class="btn btn-block" type="submit"><?= $paid ? 'Daftar dan bayar' : 'Daftar sekarang' ?></button></form>
                <?php else: ?>
                    <button class="btn btn-block" disabled><?= e($why) ?></button>
                <?php endif; ?>
                <?php if ($ev['status'] !== 'draft'): ?>
                    <a class="btn btn-light btn-block" style="margin-top:10px" href="<?= e(route('chat.index', ['event' => $ev['id']])) ?>"><?= icon('chat', 16) ?> Tanya panitia</a>
                <?php endif; ?>
            <?php elseif ($isOwner): ?>
                <div class="row-actions" style="flex-direction:column;align-items:stretch">
                    <a class="btn" href="<?= e(route('org.participants', $ev['id'])) ?>">Kelola peserta<?= (int)$ev['pending'] > 0 ? ' (' . (int)$ev['pending'] . ' menunggu)' : '' ?></a>
                    <a class="btn btn-light" href="<?= e(route('org.event.edit', $ev['id'])) ?>">Ubah acara</a>
                </div>
            <?php else: ?>
                <p class="muted" style="font-size:12px;margin:0">Acara milik panitia lain. Anda dapat melihatnya agar tidak membuat acara yang sama.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
@endsection

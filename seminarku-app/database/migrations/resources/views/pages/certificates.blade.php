@extends('layouts.app', ['title' => 'Sertifikat Saya', 'active' => 'certs', 'sub' => 'Tersimpan di akun Anda dan dapat diunduh kapan saja'])

@section('content')
<?php if (!$rows): ?>
    <div class="empty"><b>Belum ada sertifikat</b>Sertifikat terbit otomatis setelah kehadiran Anda terverifikasi pada seminar.</div>
<?php else: ?>
    <div class="course-list">
    <?php foreach ($rows as $i => $r):
        $t = strtotime((string)$r['event_date']);
        $tone = $r['id'] % 2 ? 'maroon' : 'army'; ?>
        <div class="ecard tone-<?= $tone ?>" style="cursor:default">
            <div class="edate"><b><?= date('j', $t) ?></b><span><?= e(mb_strtoupper(mb_substr(BULAN[(int)date('n', $t)], 0, 3))) ?></span><i><?= date('Y', $t) ?></i></div>
            <div class="ebody">
                <div class="etop"><span class="eyebrow"><?= e($r['category']) ?></span><?= badge('Terverifikasi', 'badge-ok') ?></div>
                <h3><?= e($r['title']) ?></h3>
                <p>Atas nama <b><?= e($r['holder_name']) ?></b> · diterbitkan <?= e(tgl(substr((string)$r['issued_at'], 0, 10))) ?> · <?= e($r['organizer_name']) ?></p>
                <div class="chips"><span class="chip"><?= icon('shield', 13) ?> Kode: <?= e($r['verify_code']) ?></span></div>
                <div class="row-actions" style="margin-top:6px">
                    <a class="btn btn-sm" href="<?= e(route('certs.pdf', $r['cert_id'])) ?>"><?= icon('download', 14) ?> Unduh PDF</a>
                    <a class="btn btn-light btn-sm" href="<?= e(route('verify', ['code' => $r['verify_code']])) ?>">Cek keaslian</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
@endsection

@extends('layouts.app', ['title' => $title, 'active' => 'org_events', 'sub' => $sub])

@section('content')
<div class="row-actions" style="margin-bottom:16px">
    <a class="btn" href="<?= e(route('org.event.create')) ?>"><?= icon('plus', 16) ?> Buat acara</a>
    <?php if ($u['role'] === 'penyelenggara'): ?><a class="btn btn-light" href="<?= e(route('org.browse')) ?>"><?= icon('compass', 16) ?> Jelajah acara lain</a><?php endif; ?>
</div>
<form class="filters" method="get" action="<?= e(route('org.events')) ?>">
    <span class="lbl">Filter</span>
    <?= filter_select('status', 'Status', ['terbit' => 'Terbit', 'draft' => 'Draf', 'ditutup' => 'Ditutup', 'selesai' => 'Sudah terlaksana'], $fStatus) ?>
    <?php if ($fStatus): ?><a class="btn-reset" href="<?= e(route('org.events')) ?>">Reset</a><?php endif; ?>
</form>
<?php if (!$rows): ?>
    <div class="empty"><b>Belum ada acara</b>Klik "Buat acara" untuk memulai.</div>
<?php else: ?>
<div class="table-wrap"><table class="tbl">
    <thead><tr><th>Acara</th><th>Tanggal</th><th>Kursi</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r):
        $cls = ['draft' => 'badge-gray', 'terbit' => 'badge-ok', 'ditutup' => 'badge-err'][$r['status']];
        $mine = (int)$r['organizer_id'] === (int)$u['id'] || $u['role'] === 'admin'; ?>
        <tr>
            <td><b><?= e($r['title']) ?></b><br><small><?= e($r['category']) ?> · <?= e(ucfirst($r['mode'])) ?> · <?= e(rupiah((int)$r['fee'])) ?><?= $u['role'] === 'admin' ? ' · ' . e($r['organizer_name']) : '' ?></small></td>
            <td><?= e(tgl($r['event_date'])) ?><br><small><?= e($r['start_time']) ?><?= event_done($r) ? ' · terlaksana' : '' ?></small></td>
            <td><?= (int)$r['registered'] ?> / <?= (int)$r['quota'] ?><?= $r['pending'] > 0 ? '<br>' . badge($r['pending'] . ' menunggu', 'badge-warn') : '' ?></td>
            <td><?= badge(ucfirst($r['status']), $cls) ?></td>
            <td><div class="row-actions">
                <a class="btn btn-light btn-sm" href="<?= e(route('org.participants', $r['id'])) ?>">Peserta</a>
                <a class="btn btn-light btn-sm" href="<?= e(route('org.event.edit', $r['id'])) ?>">Ubah</a>
                <?php if ($mine && (int)$r['registered'] === 0): ?>
                <form method="post" action="<?= e(route('org.event.delete', $r['id'])) ?>"><?= csrf_field() ?>
                    <button class="btn btn-danger btn-sm" data-confirm="Hapus acara ini?" type="submit">Hapus</button></form>
                <?php endif; ?>
            </div></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
<?php endif; ?>
@endsection

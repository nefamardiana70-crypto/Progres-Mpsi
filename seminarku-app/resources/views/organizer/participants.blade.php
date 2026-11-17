@extends('layouts.app', ['title' => 'Peserta Acara', 'active' => 'org_events', 'sub' => $ev['title']])

@section('content')
<?php
$self = route('org.participants', $ev['id']);
$act = route('org.participants.action', $ev['id']);
?>
<div class="panel">
    <h2><?= e($ev['title']) ?></h2>
    <p class="sub"><?= e(tgl($ev['event_date'], true)) ?> · <?= e($ev['start_time']) ?> WIB · <?= e($ev['location']) ?> · <?= e(rupiah((int)$ev['fee'])) ?>
        <?= $u['role'] === 'admin' ? ' · ' . e($ev['organizer_name']) : '' ?></p>
    <?= stat_tiles([[(int)$ev['registered'] . '/' . (int)$ev['quota'], 'Kursi terisi'], [$cnt['menunggu'], 'Menunggu persetujuan'], [$cnt['disetujui'], 'Disetujui'], [$cnt['hadir'], 'Hadir'], [$ev['presence_code'], 'Kode presensi hari-H']]) ?>
    <div class="row-actions">
        <a class="btn btn-sm" href="<?= e(route('org.scan', ['event' => $ev['id']])) ?>"><?= icon('scan', 14) ?> Pindai QR</a>
        <a class="btn btn-light btn-sm" href="<?= e(route('org.participants.csv', ['id' => $ev['id'], 'status' => $fStatus])) ?>"><?= icon('download', 14) ?> Ekspor CSV</a>
        <a class="btn btn-light btn-sm" href="<?= e(route('org.event.edit', $ev['id'])) ?>">Ubah acara</a>
        <?php if ($cnt['siap'] > 0): ?>
        <form method="post" action="<?= e($act) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="setujui_semua">
            <button class="btn btn-army btn-sm" data-confirm="Setujui <?= $cnt['siap'] ?> pendaftaran yang siap?" type="submit"><?= icon('check', 14) ?> Setujui semua yang siap (<?= $cnt['siap'] ?>)</button></form>
        <?php endif; ?>
        <form method="post" action="<?= e($act) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="kode_baru">
            <button class="btn btn-light btn-sm" data-confirm="Buat kode presensi baru? Kode lama tidak berlaku lagi." type="submit">Kode presensi baru</button></form>
    </div>
    <?php if (!$canMark && $ev['status'] !== 'draft'): ?><p class="muted" style="font-size:12px;margin:12px 0 0">Presensi (QR maupun kode) baru dibuka pada hari acara.</p><?php endif; ?>
</div>

<form class="filters" method="get" action="<?= e($self) ?>">
    <span class="lbl">Filter</span>
    <?= filter_select('status', 'Status', ['menunggu' => 'Menunggu persetujuan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'hadir' => 'Sudah hadir', 'belum' => 'Disetujui, belum hadir'], $fStatus) ?>
    <?php if ($fStatus): ?><a class="btn-reset" href="<?= e($self) ?>">Reset</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="empty"><b>Tidak ada peserta</b>Peserta yang mendaftar akan muncul di sini.</div>
<?php else: ?>
<div class="table-wrap"><table class="tbl">
    <thead><tr><th>#</th><th>Peserta</th><?php if ($paid): ?><th>Bukti bayar</th><?php endif; ?><th>Status</th><th>Kehadiran</th><th>Tindakan</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r): [$label, $cls] = reg_state($r, $ev); ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><b><?= e($r['name']) ?></b><br><small><?= e((string)$r['nim']) ?> · <?= e($r['email']) ?></small></td>
            <?php if ($paid): ?>
            <td><?php if ($r['payment_proof']): ?><a target="_blank" rel="noopener" href="<?= e(upload_url('proof', $r['id'])) ?>" title="Buka bukti pembayaran"><img class="proof-thumb" src="<?= e(upload_url('proof', $r['id'])) ?>" alt="Bukti pembayaran"></a><br><small><?= e(substr((string)$r['proof_at'], 0, 16)) ?></small>
                <?php else: ?><small>Belum ada</small><?php endif; ?></td>
            <?php endif; ?>
            <td><?= badge($label, $cls) ?><?= $r['status'] === 'ditolak' && $r['reject_reason'] ? '<br><small>' . e($r['reject_reason']) . '</small>' : '' ?></td>
            <td><?= $r['attended'] ? badge('Hadir', 'badge-ok') . '<br><small>' . e(substr($r['attended_at'], 0, 16)) . '</small>' : '<small>—</small>' ?>
                <?php if ($r['cert_id']): ?><br><a class="link" style="font-size:11.5px" href="<?= e(route('certs.pdf', $r['cert_id'])) ?>">Sertifikat</a><?php endif; ?></td>
            <td>
                <form method="post" action="<?= e($act) ?>" class="row-actions"><?= csrf_field() ?><input type="hidden" name="reg" value="<?= (int)$r['id'] ?>">
                    <?php if (!$r['attended'] && $r['status'] !== 'disetujui'): ?>
                        <?php if ($paid && empty($r['payment_proof'])): ?>
                            <button class="btn btn-light btn-sm" name="action" value="setujui" data-confirm="Setujui tanpa bukti pembayaran (mis. dibayar tunai)?"
                                onclick="this.form.manual.value=1">Setujui manual</button><input type="hidden" name="manual" value="0">
                        <?php else: ?>
                            <button class="btn btn-army btn-sm" name="action" value="setujui">Setujui</button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'disetujui' && !$r['attended'] && $canMark): ?>
                        <button class="btn btn-sm" name="action" value="hadir">Tandai hadir</button>
                    <?php endif; ?>
                    <?php if (!$r['attended'] && $r['status'] !== 'ditolak'): ?>
                        <input type="text" name="reason" placeholder="Alasan jika ditolak" style="width:150px;padding:6px 10px;font-size:12px">
                        <button class="btn btn-danger btn-sm" name="action" value="tolak" data-confirm="Tolak pendaftaran ini?">Tolak</button>
                    <?php endif; ?>
                    <?php if (!$r['attended']): ?>
                        <button class="btn btn-light btn-sm" name="action" value="hapus" data-confirm="Hapus peserta ini dari acara?" title="Hapus"><?= icon('x', 14) ?></button>
                    <?php endif; ?>
                    <?php if ($ownerActs): ?><a class="btn btn-light btn-sm" href="<?= e(route('chat.index', ['event' => $ev['id'], 'with' => $r['user_id']])) ?>" title="Kirim pesan"><?= icon('chat', 14) ?></a><?php endif; ?>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
<?php endif; ?>
@endsection

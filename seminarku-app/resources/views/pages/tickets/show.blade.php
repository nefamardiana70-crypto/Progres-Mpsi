@extends('layouts.app', ['title' => 'Tiket saya', 'active' => 'tickets'])

@section('content')
<div class="hero-event tone-<?= $tone ?>">
    <div class="edate"><b><?= date('j', $t) ?></b><span><?= e(mb_strtoupper(mb_substr(BULAN[(int)date('n', $t)], 0, 3))) ?></span><i><?= date('Y', $t) ?></i></div>
    <div class="he-body">
        <div class="badges"><?= badge($lbl, $cls) ?><?= $paid ? badge('Berbayar ' . rupiah((int)$ev['fee']), 'badge-warn') : badge('Gratis', 'badge-ok') ?></div>
        <h2><a href="<?= e(route('events.show', $ev['id'])) ?>"><?= e($ev['title']) ?></a></h2>
        <div class="muted"><?= e(tgl($ev['event_date'], true)) ?> · <?= e($ev['start_time']) ?> WIB · <?= e($ev['location']) ?></div>
    </div>
</div>

<div class="panel">
    <ol class="stepper">
        <?php foreach ($steps as [$name, $state]): ?><li class="<?= e($state) ?>"><?= e($name) ?></li><?php endforeach; ?>
    </ol>
</div>

<div class="detail">
    <div>
        <?php if ($rejected): ?>
            <div class="alert alert-error"><b>Pendaftaran ditolak.</b> <?= $reg['reject_reason'] ? 'Alasan: ' . e($reg['reject_reason']) : 'Tidak ada alasan yang dicantumkan.' ?>
                <a class="link" href="<?= e(route('chat.index', ['event' => $ev['id']])) ?>">Tanyakan ke panitia</a></div>
        <?php endif; ?>

        <?php if ($needPay): ?>
        <div class="panel">
            <h2>Pembayaran</h2>
            <p class="sub">Bayar ke penyelenggara, lalu unggah bukti. Panitia menyetujui pendaftaran setelah memeriksanya.</p>
            <div class="pay-box">
                <div>
                    <?php if ($ev['qris_image']): ?>
                        <img class="qris-img" src="<?= e(upload_url('qris', $ev['id'])) ?>" alt="QRIS pembayaran <?= e($ev['title']) ?>">
                    <?php else: ?>
                        <div class="ticket-lock" style="padding:10px 0"><div class="lk"><?= icon('wallet', 22) ?></div><small>Panitia tidak melampirkan QRIS. Ikuti petunjuk di samping.</small></div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="muted" style="font-size:12px">Jumlah yang dibayar</div>
                    <div class="pay-amount"><?= e(rupiah((int)$ev['fee'])) ?></div>
                    <?php if (trim((string)$ev['payment_info']) !== ''): ?><div class="pay-info"><?= e($ev['payment_info']) ?></div><?php endif; ?>
                    <form method="post" action="<?= e(route('tickets.proof', $reg['id'])) ?>" enctype="multipart/form-data" class="stack"><?= csrf_field() ?>
                        <div class="field"><label for="proof"><?= $reg['payment_proof'] ? 'Ganti bukti pembayaran' : 'Bukti pembayaran' ?></label>
                            <input id="proof" type="file" name="proof" accept="image/png,image/jpeg,image/webp" data-preview required>
                            <div class="hint">JPG, PNG, atau WebP, maksimal 2 MB.</div></div>
                        <div><button class="btn" type="submit"><?= icon('upload', 16) ?> Kirim bukti</button></div>
                    </form>
                    <?php if ($reg['payment_proof']): ?>
                        <p class="muted" style="font-size:12px;margin:14px 0 6px">Bukti terkirim <?= e(ago((string)$reg['proof_at'])) ?>. Menunggu pemeriksaan panitia.</p>
                        <img class="proof-full" src="<?= e(upload_url('proof', $reg['id'])) ?>" alt="Bukti pembayaran Anda">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="panel">
            <h2>Rincian</h2>
            <ul class="facts" style="margin-top:14px">
                <li><?= icon('ticket', 18) ?><span>Pendaftaran<small><?= e($lbl) ?></small></span></li>
                <li><?= icon('check', 18) ?><span>Kehadiran<small><?= $reg['attended'] ? 'Hadir · ' . e(tgl(substr((string)$reg['attended_at'], 0, 10))) . ' ' . e(substr((string)$reg['attended_at'], 11, 5)) : 'Belum tercatat' ?></small></span></li>
                <li><?= icon('award', 18) ?><span>E-sertifikat<small><?= $cert ? 'Sudah terbit' : ($ev['has_certificate'] ? 'Terbit setelah kehadiran tercatat' : 'Acara ini tidak menerbitkan sertifikat') ?></small></span></li>
            </ul>
            <div class="row-actions" style="margin-top:16px">
                <?php if ($cert): ?><a class="btn" href="<?= e(route('certs.pdf', $cert['id'])) ?>"><?= icon('download', 16) ?> Unduh sertifikat</a><?php endif; ?>
                <a class="btn btn-light" href="<?= e(route('chat.index', ['event' => $ev['id']])) ?>"><?= icon('chat', 16) ?> Pesan ke panitia</a>
            </div>
        </div>

        <?php if ($approved && !$reg['attended'] && (string)$ev['event_date'] === $today): ?>
        <div class="panel">
            <h2>Presensi dengan kode</h2>
            <p class="sub">QR tidak terbaca? Masukkan kode presensi yang diumumkan panitia hari ini.</p>
            <form method="post" action="<?= e(route('tickets.presence', $reg['id'])) ?>" class="row-actions"><?= csrf_field() ?>
                <input type="text" name="code" inputmode="numeric" maxlength="6" placeholder="6 digit kode" style="max-width:200px" required>
                <button class="btn" type="submit">Kirim kehadiran</button></form>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="ticket">
            <div class="tk-title"><?= e($ev['title']) ?></div>
            <div class="tk-sub"><?= e(tgl($ev['event_date'])) ?> · <?= e($ev['start_time']) ?> WIB</div>
            <?php if ($qrOk): ?>
                <div class="qr" data-qr="<?= e($reg['ticket_code']) ?>"></div>
                <div class="code"><?= e($reg['ticket_code']) ?></div>
                <p class="tk-note">Tunjukkan QR ini kepada panitia untuk dipindai.</p>
            <?php else:
                if ($reg['attended']) { $lt = ['Sudah hadir', 'Kehadiran Anda sudah tercatat. QR tidak berlaku lagi.']; }
                elseif ($rejected) { $lt = ['Pendaftaran ditolak', 'Tiket tidak diterbitkan.']; }
                elseif ($approved) { $lt = ['Acara telah berlalu', 'QR tidak berlaku setelah hari acara.']; }
                elseif ($paid && !$reg['payment_proof']) { $lt = ['QR belum tersedia', 'Bayar dan unggah bukti pembayaran. QR muncul setelah panitia menyetujui.']; }
                else { $lt = ['Menunggu persetujuan panitia', 'QR muncul otomatis setelah pendaftaran Anda disetujui.']; }
            ?>
                <div class="ticket-lock"><div class="lk"><?= icon('lock', 24) ?></div><b><?= e($lt[0]) ?></b><span><?= e($lt[1]) ?></span></div>
                <div class="code" style="font-size:12px;opacity:.7"><?= e($reg['ticket_code']) ?></div>
            <?php endif; ?>
        </div>
        <?php if (!$reg['attended']): ?>
            <form method="post" action="<?= e(route('tickets.cancel', $reg['id'])) ?>" style="margin-top:14px"><?= csrf_field() ?>
                <button class="btn btn-danger btn-sm" data-confirm="Batalkan pendaftaran pada acara ini?" type="submit">Batalkan pendaftaran</button></form>
        <?php endif; ?>
    </div>
</div>
@endsection

<form method="get" action="<?= e(route('verify')) ?>" class="filters" style="margin-top:6px">
    <div class="search-pill"><label class="sr" for="code">Kode verifikasi</label><input id="code" name="code" type="text" value="<?= e($code) ?>" placeholder="Contoh: CERT-1A2B3C4D5E" autocomplete="off" autofocus></div>
    <button class="btn" type="submit">Periksa</button>
</form>
<?php if ($code !== '' && $cert): ?>
    <div class="verify-card verify-ok">
        <?= badge('Sertifikat valid', 'badge-ok') ?>
        <h2 style="margin:10px 0 4px;font-size:22px"><?= e($cert['holder_name']) ?></h2>
        <ul class="facts" style="margin-top:14px">
            <li><?= icon('award', 18) ?><span><?= e($cert['title']) ?><small>Diselenggarakan oleh <?= e($cert['organizer_name']) ?></small></span></li>
            <li><?= icon('calendar', 18) ?><span><?= e(tgl($cert['event_date'], true)) ?><small><?= e($cert['location']) ?></small></span></li>
            <li><?= icon('check', 18) ?><span>Kehadiran terverifikasi<small>Tercatat <?= e(tgl(substr((string)$cert['attended_at'], 0, 10), true)) ?> · terbit <?= e(tgl(substr((string)$cert['issued_at'], 0, 10), true)) ?></small></span></li>
        </ul>
        <p class="muted" style="font-size:11.5px;margin:14px 0 0">Kode: <?= e($cert['verify_code']) ?>. Keabsahan sebagai syarat sidang tetap menjadi kewenangan program studi.</p>
    </div>
<?php elseif ($code !== ''): ?>
    <div class="verify-card verify-bad"><?= badge('Tidak ditemukan', 'badge-err') ?>
        <p style="margin:10px 0 0">Kode <b><?= e($code) ?></b> tidak terdaftar di sistem. Periksa kembali penulisannya.</p></div>
<?php endif; ?>

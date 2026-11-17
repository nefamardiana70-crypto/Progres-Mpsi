@extends('layouts.public', ['title' => 'Beranda'])

@section('content')
<section class="hero">
    <div>
        <h1>Seminar kampus, tertata rapi.</h1>
        <p class="lead">Temukan acara, daftar, dan simpan sertifikat Anda di satu tempat.</p>
        <div class="hero-cta">
            <?php if (user()): ?>
                <a class="btn btn-lg" href="<?= e(route('dashboard')) ?>">Buka beranda</a>
            <?php else: ?>
                <a class="btn btn-lg" href="<?= e(route('register')) ?>">Daftar gratis</a>
                <a class="btn btn-lg btn-light" href="<?= e(route('login')) ?>">Masuk</a>
            <?php endif; ?>
        </div>
        <div class="hero-meta">
            <?php foreach ($stats as [$v, $l]): ?><div><b><?= $v ?></b><span><?= e($l) ?></span></div><?php endforeach; ?>
        </div>
    </div>
    <div class="hv" aria-hidden="true">
        <div class="hv-bg"></div>
        <div class="hv-card hv-cert"><b style="font-size:12px">Sertifikat</b><div class="cn"></div><div class="cl w1"></div><div class="cl w2"></div><div class="cl w3"></div><span class="seal"><?= icon('award', 18) ?></span></div>
        <div class="hv-card hv-cal">
            <h5>Jadwal <span><?= e(BULAN[(int)date('n')]) ?></span></h5>
            <div class="hv-grid"><i></i><i class="m t"></i><i></i><i class="a"></i><i></i><i class="a t"></i><i></i><i class="m"></i><i></i><i class="m"></i><i></i><i class="a"></i><i></i><i></i><i class="m"></i></div>
        </div>
        <div class="hv-card hv-ticket"><b>Tiket</b><div class="qrm"><?= icon('lock', 44) ?></div><small>AKTIF SETELAH DISETUJUI</small></div>
        <span class="hv-pill hv-p1"><?= icon('check', 14) ?> Disetujui panitia</span>
        <span class="hv-pill hv-p2"><?= icon('chat', 14) ?> Tanya panitia</span>
    </div>
</section>

<section class="land-sec">
    <div class="steps3">
        <div class="step3"><span class="si"><?= icon('search', 22) ?></span><div><b>Cari acara</b><span>Saring menurut tanggal, biaya, dan mode.</span></div></div>
        <div class="step3"><span class="si"><?= icon('ticket', 22) ?></span><div><b>Daftar dan dapat tiket</b><span>QR muncul setelah panitia menyetujui.</span></div></div>
        <div class="step3"><span class="si"><?= icon('award', 22) ?></span><div><b>Hadir dan terima sertifikat</b><span>Terbit otomatis, bisa diverifikasi.</span></div></div>
    </div>
</section>

<section class="land-sec" id="acara">
    <div class="sec-head"><h2>Acara berikutnya</h2><a href="<?= e(route('events.index')) ?>">Lihat semua</a></div>
    <?php if (!$upcoming->count()): ?>
        <div class="empty"><b>Belum ada acara terbuka</b>Acara baru akan tampil di sini.</div>
    <?php else: ?>
        <div class="glass-grid">
        <?php foreach ($upcoming as $ev): $t = strtotime((string)$ev['event_date']); ?>
            <a class="gcard" href="<?= e(route('events.show', $ev['id'])) ?>">
                <?php if (!empty($ev['poster'])): ?><span class="gposter"><img loading="lazy" src="<?= e(upload_url('poster', $ev['id'])) ?>" alt="Poster <?= e($ev['title']) ?>"></span><?php endif; ?>
                <div class="gd"><b><?= date('j', $t) ?></b><span><?= e(BULAN[(int)date('n', $t)]) ?> · <?= e($ev['start_time']) ?> WIB</span></div>
                <h3><?= e($ev['title']) ?></h3>
                <div class="chips">
                    <span class="chip"><?= icon('pin', 13) ?><?= e(ucfirst($ev['mode'])) ?></span>
                    <span class="chip"><?= icon('wallet', 13) ?><?= e(rupiah((int)$ev['fee'])) ?></span>
                    <?php if ($ev['has_certificate']): ?><span class="chip"><?= icon('award', 13) ?>Sertifikat</span><?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
@endsection

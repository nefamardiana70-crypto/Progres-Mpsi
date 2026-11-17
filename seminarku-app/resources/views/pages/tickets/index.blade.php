@extends('layouts.app', ['title' => 'Tiket saya', 'active' => 'tickets', 'sub' => 'Status pendaftaran dan QR tiket Anda'])

@section('content')
<?php if (!count($mine)): ?>
    <div class="empty"><b>Belum ada tiket</b>Daftar ke sebuah acara dan tiketnya muncul di sini. <a class="link" href="<?= e(route('events.index')) ?>">Cari seminar</a></div>
<?php else: ?>
    <div class="course-list">
    <?php foreach ($mine as $i => $m) {
        $ev = get_event((int)$m['event_id']);
        $reg = ['status' => $m['reg_status'], 'attended' => $m['attended'], 'payment_proof' => $m['payment_proof']];
        [$lbl, $cls] = reg_state($reg, $ev);
        $days = (int)((strtotime((string)$ev['event_date']) - strtotime(date('Y-m-d'))) / 86400);
        $foot = ticket_qr_visible($reg, $ev)
            ? '<b>QR siap dipakai</b>' . ($days === 0 ? ' · hari ini' : ' · ' . $days . ' hari lagi')
            : ($m['reg_status'] === 'menunggu' ? 'QR muncul setelah <b>panitia menyetujui</b>' : 'Buka untuk melihat rincian');
        echo event_card($ev, route('tickets.show', $m['reg_id']), $i, badge($lbl, $cls), $foot);
    } ?>
    </div>
<?php endif; ?>
@endsection

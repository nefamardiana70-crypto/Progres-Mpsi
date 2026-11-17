@extends('layouts.app', ['title' => 'Presensi Peserta', 'active' => 'org_scan', 'sub' => 'Pindai QR tiket peserta atau ketik kode tiketnya'])

@section('content')
<?php if (!count($evs)): ?>
    <div class="empty"><b>Belum ada acara terbit</b>Publikasikan acara terlebih dahulu untuk memulai presensi.</div>
<?php else: ?>
<div class="grid2">
    <div class="panel">
        <h2>Pindai QR Code</h2>
        <p class="sub">Hanya tiket yang sudah <b>disetujui</b> yang diterima. Kamera memerlukan HTTPS atau localhost.</p>
        <div class="field" style="margin-bottom:14px"><label for="event">Acara</label>
            <select id="event" class="input"><?php foreach ($evs as $e_): ?>
                <option value="<?= (int)$e_['id'] ?>" <?= $selected === (int)$e_['id'] ? 'selected' : '' ?>><?= e($e_['title']) ?> (<?= e(tgl($e_['event_date'])) ?>)</option><?php endforeach; ?></select></div>
        <div id="reader" style="max-width:420px;border-radius:14px;overflow:hidden"></div>
        <div id="result" class="scan-result"></div>
    </div>
    <div class="panel">
        <h2>Input kode manual</h2>
        <p class="sub">Jika QR tidak terbaca, ketik kode tiket peserta (contoh: TKT-1A2B3C4D5E).</p>
        <form id="manual" class="row-actions"><input id="code" type="text" placeholder="TKT-XXXXXXXXXX" autocomplete="off" style="max-width:260px" required>
            <button class="btn" type="submit">Catat hadir</button></form>
        <p class="muted" style="font-size:12px;margin-top:16px">Peserta juga dapat presensi mandiri memakai <b>kode presensi hari-H</b> (lihat di halaman peserta acara).</p>
    </div>
</div>
<?php endif; ?>
@endsection

@push('scripts')
<?php if (count($evs)): ?>
<script>
const CSRF = <?= json_encode(csrf_token()) ?>;
const CHECKIN_URL = <?= json_encode(route('org.scan.checkin')) ?>;
const box = document.getElementById('result');
let busy = false;
async function checkin(code) {
  if (busy) return; busy = true;
  const body = new URLSearchParams({_token: CSRF, event: document.getElementById('event').value, code: code});
  try {
    const r = await (await fetch(CHECKIN_URL, {method: 'POST', body, credentials: 'same-origin', headers: {'Accept': 'application/json'}})).json();
    box.className = 'scan-result show alert alert-' + (r.ok ? 'ok' : 'error');
    box.textContent = (r.name ? r.name + ' — ' : '') + r.msg;
  } catch (e) {
    box.className = 'scan-result show alert alert-error'; box.textContent = 'Gagal menghubungi server.';
  }
  setTimeout(() => busy = false, 2500);
}
document.getElementById('manual').addEventListener('submit', ev => {
  ev.preventDefault(); const i = document.getElementById('code'); checkin(i.value.trim()); i.value = '';
});
if (window.Html5QrcodeScanner) {
  new Html5QrcodeScanner('reader', {fps: 10, qrbox: 240}, false).render(txt => checkin(txt), () => {});
} else {
  document.getElementById('reader').outerHTML = '<p class="muted">Pemindai kamera tidak dapat dimuat. Gunakan input kode manual.</p>';
}
</script>
<?php endif; ?>
@endpush

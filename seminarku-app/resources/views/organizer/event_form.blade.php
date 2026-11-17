@extends('layouts.app', ['title' => $title, 'active' => $active, 'sub' => 'Sistem memeriksa otomatis agar acara tidak ganda atau bentrok tempat'])

@section('content')
<form method="post" action="<?= e($ev ? route('org.event.update', $ev->id) : route('org.event.store')) ?>" enctype="multipart/form-data" class="stack"><?= csrf_field() ?>
    <div class="form-sec stack"><h3>Poster acara</h3><p class="sub">Gambar promosi seperti postingan Instagram. Tampil di daftar acara, galeri, beranda, dan halaman detail agar calon peserta langsung tertarik. Ini bukan gambar QRIS pembayaran.</p>
        <div class="poster-up">
            <div class="poster-prev" id="posterPrev"><?php if (!empty($d['poster']) && $ev): ?><img src="<?= e(upload_url('poster', $ev['id'])) ?>" alt="Poster saat ini"><?php else: ?><span><?= icon('upload', 26) ?>Belum ada poster</span><?php endif; ?></div>
            <div>
                <label class="file-btn" for="poster"><?= icon('upload', 16) ?> <span id="posterName"><?= !empty($d['poster']) && $ev ? 'Ganti poster' : 'Pilih poster' ?></span></label>
                <input id="poster" name="poster" class="file-hidden" type="file" accept="image/jpeg,image/png,image/webp" data-poster>
                <div class="hint">JPG, PNG, atau WebP, maksimal 4 MB. Disarankan rasio 4:5 (potret), misalnya 1080 x 1350 piksel.</div>
                <?php if (!empty($d['poster']) && $ev): ?><div class="checks" style="margin-top:8px"><label><input type="checkbox" name="remove_poster" value="1"> Hapus poster ini</label></div><?php endif; ?>
            </div>
        </div></div>
    <div class="form-sec stack"><h3>Informasi acara</h3><p class="sub">Judul dan deskripsi yang dilihat calon peserta.</p>
    <div class="field"><label for="title">Judul acara</label><input id="title" name="title" type="text" required value="<?= e($d['title']) ?>"></div>
    <div class="field"><label for="description">Deskripsi</label><textarea id="description" name="description" required><?= e($d['description']) ?></textarea></div>
    <div class="grid2">
        <div class="field"><label for="category">Kategori</label>
            <select id="category" name="category" class="input"><?php foreach (CATEGORIES as $c): ?><option <?= $d['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="mode">Mode pelaksanaan</label>
            <select id="mode" name="mode" class="input"><option value="offline" <?= $d['mode'] === 'offline' ? 'selected' : '' ?>>Offline</option><option value="online" <?= $d['mode'] === 'online' ? 'selected' : '' ?>>Online</option></select></div>
    </div>
    </div>
    <div class="form-sec stack"><h3>Waktu dan tempat</h3><p class="sub">Sistem mengecek bentrok lokasi dan jam dengan acara lain.</p>
    <div class="grid2">
        <div class="field"><label for="event_date">Tanggal acara</label><input id="event_date" name="event_date" type="date" required value="<?= e($d['event_date']) ?>" <?= $ev ? '' : 'min="' . $today . '"' ?>></div>
        <div class="field"><label for="start_time">Jam mulai (WIB)</label><input id="start_time" name="start_time" type="time" required value="<?= e($d['start_time']) ?>"></div>
    </div>
    <div id="dayCheck" class="alert alert-info" hidden style="margin:0"></div>
    <div class="grid2">
        <div class="field"><label for="reg_deadline">Batas pendaftaran</label><input id="reg_deadline" name="reg_deadline" type="date" value="<?= e((string)$d['reg_deadline']) ?>"></div>
        <div class="field"><label for="location">Lokasi / tautan acara</label><input id="location" name="location" type="text" required value="<?= e($d['location']) ?>"></div>
    </div>
    </div>
    <div class="form-sec stack"><h3>Biaya dan kuota</h3><p class="sub">Acara berbayar menampilkan QRIS dan petunjuk transfer kepada peserta terdaftar.</p>
    <div class="grid2">
        <div class="field"><label for="fee">Biaya (Rp, isi 0 jika gratis)</label>
            <input id="fee" name="fee" type="number" min="0" step="1000" value="<?= (int)$d['fee'] ?>" <?= $ev && (int)$d['registered'] > 0 ? 'readonly' : '' ?>>
            <div class="hint"><?= $ev && (int)$d['registered'] > 0 ? 'Biaya dikunci karena sudah ada pendaftar.' : 'Acara berbayar: peserta membayar via QRIS/transfer lalu mengunggah bukti.' ?></div></div>
        <div class="field"><label for="quota">Kuota peserta</label><input id="quota" name="quota" type="number" min="1" value="<?= (int)$d['quota'] ?>"></div>
    </div>

    <div id="pay-fields" class="stack pay-sec" <?= (int)$d['fee'] > 0 ? '' : 'hidden' ?>>
        <h3>Pembayaran QRIS dan transfer</h3>
        <div class="grid2">
            <div class="field"><label for="qris">Gambar kode QRIS pembayaran (maks. 2 MB)</label>
                <input id="qris" name="qris" type="file" accept="image/jpeg,image/png,image/webp">
                <?php if (!empty($d['qris_image']) && $ev): ?>
                    <div style="margin-top:10px"><img class="qris-img" style="max-width:150px" src="<?= e(upload_url('qris', $ev['id'])) ?>" alt="QRIS saat ini">
                    <div class="checks" style="margin-top:6px"><label><input type="checkbox" name="remove_qris" value="1"> Hapus QRIS ini</label></div></div>
                <?php endif; ?>
                <div class="hint">Kode QRIS untuk membayar. Hanya tampil kepada peserta yang sudah mendaftar acara ini, bukan di poster.</div></div>
            <div class="field"><label for="payment_info">Petunjuk pembayaran</label>
                <textarea id="payment_info" name="payment_info" style="min-height:104px" placeholder="Contoh: Transfer ke Bank BSI 1234567890 a.n. Bendahara Panitia"><?= e((string)$d['payment_info']) ?></textarea></div>
        </div>
    </div>

    </div>
    <div class="form-sec stack"><h3>Publikasi</h3><p class="sub">Draf tidak terlihat oleh peserta.</p>
    <div class="grid2">
        <div class="field"><label for="status">Status</label>
            <select id="status" name="status" class="input">
                <?php foreach (['draft' => 'Draf (belum tampil)', 'terbit' => 'Terbit (pendaftaran dibuka)', 'ditutup' => 'Ditutup'] as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $d['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
            </select></div>
        <div class="field"><label>Pengaturan</label>
            <div class="checks" style="flex-direction:column;gap:8px;padding-top:6px">
                <label><input type="checkbox" name="has_certificate" value="1" <?= $d['has_certificate'] ? 'checked' : '' ?>> Terbitkan e-sertifikat otomatis</label>
                <label id="autoWrap"><input type="checkbox" name="auto_approve" value="1" <?= ($d['approval_mode'] ?? 'manual') === 'auto' ? 'checked' : '' ?>> Setujui pendaftar otomatis (hanya acara gratis)</label>
            </div></div>
    </div>

    </div>

    <?php if ($needConfirm): $o = $conflicts['dup']; ?>
        <div class="alert alert-warn">
            <b>Acara serupa ditemukan:</b> <?= e($o['title']) ?> — <?= e($o['organizer_name']) ?>, <?= e(tgl($o['event_date'])) ?>.
            <div class="checks" style="margin-top:8px"><label><input type="checkbox" name="confirm_similar" value="1"> Acara saya berbeda, tetap simpan</label></div>
        </div>
    <?php endif; ?>

    <div class="row-actions"><button class="btn" type="submit"><?= $ev ? 'Simpan perubahan' : 'Simpan acara' ?></button>
        <a class="btn btn-light" href="<?= e(route('org.events')) ?>">Batal</a>
        <a class="btn btn-light" href="<?= e(route('org.browse')) ?>" target="_blank" rel="noopener"><?= icon('compass', 16) ?> Lihat acara lain</a></div>
</form>
<script>
(function () {
  var pi = document.querySelector('[data-poster]'), pp = document.getElementById('posterPrev');
  if (pi) pi.addEventListener('change', function () {
    var f = pi.files && pi.files[0]; if (!f) return;
    document.getElementById('posterName').textContent = f.name;
    var img = document.createElement('img'); img.alt = 'Pratinjau poster'; img.src = URL.createObjectURL(f);
    pp.textContent = ''; pp.appendChild(img);
  });
})();
(function () {
  var date = document.getElementById('event_date'), box = document.getElementById('dayCheck'), fee = document.getElementById('fee'), auto = document.getElementById('autoWrap');
  var pay = document.getElementById('pay-fields');
  function syncFee() {
    var paid = parseInt(fee.value || '0', 10) > 0;
    auto.style.opacity = paid ? .45 : 1;
    pay.hidden = !paid;                       // bagian QRIS hanya untuk acara berbayar
  }
  fee.addEventListener('input', syncFee); fee.addEventListener('change', syncFee); syncFee();
  function check() {
    if (!date.value) return;
    fetch(<?= json_encode(route('org.browse.check')) ?> + '?date=' + encodeURIComponent(date.value) + '&exclude=<?= $ev ? (int)$ev['id'] : 0 ?>', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(function (j) {
        box.textContent = '';
        if (!j.events.length) { box.hidden = true; return; }
        var b = document.createElement('b'); b.textContent = 'Acara lain pada tanggal ini:'; box.appendChild(b);
        j.events.forEach(function (e) {
          var d = document.createElement('div'); d.textContent = '• ' + e.start_time + ' WIB — ' + e.title + ' (' + e.organizer + ', ' + e.location + ')'; box.appendChild(d);
        });
        box.hidden = false;
      }).catch(function () {});
  }
  date.addEventListener('change', check); check();
})();
</script>
@endsection

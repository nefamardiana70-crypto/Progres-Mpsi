@extends('layouts.app', ['title' => 'Jelajah Acara', 'active' => 'org_browse', 'sub' => 'Acara dari semua penyelenggara — periksa sebelum membuat acara baru agar tidak ganda'])

@section('content')
<div class="alert alert-info">Gunakan halaman ini untuk melihat acara yang <b>akan</b> dan <b>sudah terlaksana</b> milik penyelenggara lain. Saat Anda membuat acara, sistem juga memeriksa judul yang mirip dan bentrok lokasi secara otomatis.</div>
<form class="filters" method="get" action="<?= e(route('org.browse')) ?>">
    <div class="search-pill"><label class="sr" for="q">Cari</label><input id="q" type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul, lokasi, atau topik…"></div>
    <button class="btn btn-sm" type="submit">Cari</button>
</form>
<form class="filters" method="get" action="<?= e(route('org.browse')) ?>">
    <input type="hidden" name="q" value="<?= e($q) ?>">
    <span class="lbl">Filter</span>
    <label class="pill"><span class="sr">Waktu</span><select name="waktu" data-autosubmit>
        <option value="datang" <?= $when === 'datang' ? 'selected' : '' ?>>Akan datang</option>
        <option value="lalu" <?= $when === 'lalu' ? 'selected' : '' ?>>Sudah terlaksana</option>
        <option value="semua" <?= $when === 'semua' ? 'selected' : '' ?>>Semua</option>
    </select></label>
    <?= filter_select('penyelenggara', 'Penyelenggara', $orgOpts, (string)$org) ?>
    <?= filter_select('kategori', 'Kategori', array_combine(CATEGORIES, CATEGORIES), $cat) ?>
    <?php if ($filtered): ?><a class="btn-reset" href="<?= e(route('org.browse')) ?>">Reset</a><?php endif; ?>
</form>
<p class="muted" style="margin:0 0 14px;font-size:12.5px"><?= count($rows) ?> acara</p>
<?php if (!$rows): ?>
    <div class="empty"><b>Tidak ada acara</b>Belum ada acara yang cocok dengan filter ini.</div>
<?php else: ?>
    <div class="course-list">
    <?php foreach ($rows as $i => $r):
        $mine = (int)$r['organizer_id'] === (int)$u['id'];
        $badge = ($mine ? '<span class="badge badge-info">Acara Anda</span>' : '')
            . (event_done($r) ? '<span class="badge badge-gray">Terlaksana</span>' : '')
            . ($r['status'] === 'ditutup' ? '<span class="badge badge-err">Ditutup</span>' : '');
        $foot = 'Diselenggarakan oleh <b>' . e($r['organizer_name']) . '</b> · ' . e($r['location']);
        echo event_card($r, $mine || $u['role'] === 'admin' ? route('org.participants', $r['id']) : route('events.show', $r['id']), $i, $badge, $foot);
    endforeach; ?>
    </div>
<?php endif; ?>
@endsection

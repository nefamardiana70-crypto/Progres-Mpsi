@extends('layouts.app', ['title' => $title, 'active' => 'chat', 'sub' => $sub])

@section('content')
<?php
/* $href: tautan percakapan; $cur: percakapan yang sedang dibuka */
$href = fn(array $c) => route('chat.index', ['event' => $c['eid']] + ($isOrg ? ['with' => $c['pid']] : []));
?>
<div class="chat">
    <div class="chat-list">
        <h4><?= $isOrg ? 'Peserta' : 'Panitia acara' ?></h4>
        <?php foreach ($convs as $c): $on = $cur && $c['eid'] === $cur['eid'] && $c['pid'] === $cur['pid']; ?>
            <a class="conv <?= $on ? 'active' : '' ?>" href="<?= e($href($c)) ?>">
                <span class="mini-ava"><?= e(initials($c['name'])) ?></span>
                <span class="conv-t"><b><?= e($c['name']) ?></b><small><?= e(mb_strimwidth($c['title'], 0, 34, '…')) ?></small><?= $c['org'] !== '' ? '<small>Panitia: ' . e(mb_strimwidth($c['org'], 0, 28, '…')) . '</small>' : '' ?>
                    <small><?= $c['last'] ? ($c['last']['sender_id'] == $me ? 'Anda: ' : '') . e(mb_strimwidth($c['last']['body'], 0, 30, '…')) : 'Belum ada pesan' ?></small></span>
                <span class="conv-meta"><?= $c['last'] ? e(ago($c['last']['created_at'])) : '' ?><?= $c['unread'] > 0 ? '<em class="nbadge">' . $c['unread'] . '</em>' : '' ?></span>
            </a>
        <?php endforeach; ?>
        <?php if (!$convs): ?><div class="chat-empty"><?= $isOrg ? 'Belum ada peserta. Percakapan muncul setelah ada yang mendaftar atau menulis pesan.' : 'Belum ada percakapan. Buka halaman acara lalu pilih “Tanya panitia”.' ?></div><?php endif; ?>
    </div>

    <div class="chat-main">
        <?php if ($cur): ?>
            <div class="chat-head">
                <span class="mini-ava"><?= e(initials($cur['name'])) ?></span>
                <div><b><?= e($cur['name']) ?></b><small><?= e($cur['title']) ?><?= $cur['org'] !== '' ? ' · Panitia: ' . e($cur['org']) : '' ?></small></div>
                <a class="btn btn-light btn-sm" style="margin-left:auto" href="<?= e($isOrg ? route('org.participants', $cur['eid']) : route('events.show', $cur['eid'])) ?>"><?= $isOrg ? 'Kelola peserta' : 'Lihat acara' ?></a>
            </div>
            <div class="chat-msgs" id="msgs" data-last="<?= $lastId ?>" data-event="<?= $cur['eid'] ?>" data-with="<?= $cur['pid'] ?>">
                <?php if (!$msgs): ?><div class="chat-empty" id="emptyHint">Mulai percakapan dengan menulis pesan di bawah.</div><?php endif; ?>
                <?php foreach ($msgs as $m): ?>
                    <?php $mine = (int)$m['sender_id'] === $me; $who = $mine ? '' : ($m['srole'] === 'admin' ? 'Admin' : ''); ?>
                    <div class="msg <?= $mine ? 'me' : '' ?>"><?= $who !== '' ? '<b class="who">' . e($who) . '</b>' : '' ?><?= e($m['body']) ?><small><?= e(date('j M, H:i', strtotime($m['created_at']))) ?></small></div>
                <?php endforeach; ?>
            </div>
            <form class="chat-form" id="chatForm" method="post" action="<?= e(route('chat.send')) ?>" autocomplete="off">
                <?= csrf_field() ?><input type="hidden" name="event" value="<?= $cur['eid'] ?>"><input type="hidden" name="with" value="<?= $cur['pid'] ?>">
                <input type="text" name="body" id="chatBody" maxlength="1000" placeholder="Tulis pesan…" required aria-label="Tulis pesan">
                <button class="btn" type="submit" aria-label="Kirim"><?= icon('send', 18) ?></button>
            </form>
        <?php else: ?>
            <div class="chat-empty"><?= icon('chat', 34) ?><br><br><b style="color:var(--ink)">Pilih percakapan</b><br><?= $isOrg ? 'Pilih peserta di sebelah kiri untuk mulai berkomunikasi.' : 'Pilih acara di sebelah kiri, atau mulai dari halaman acara.' ?></div>
        <?php endif; ?>
    </div>
</div>
@endsection

@push('scripts')
<?php if ($cur): ?>
<script>
(function () {
  var API = <?= json_encode(route('chat.api')) ?>;
  var box = document.getElementById('msgs'), form = document.getElementById('chatForm'), input = document.getElementById('chatBody');
  var last = parseInt(box.dataset.last, 10) || 0, ev = box.dataset.event, wi = box.dataset.with, busy = false;
  function down() { box.scrollTop = box.scrollHeight; }
  function add(m) {
    var hint = document.getElementById('emptyHint'); if (hint) hint.remove();
    var d = document.createElement('div'); d.className = 'msg' + (m.mine ? ' me' : '');
    if (m.who) { var w = document.createElement('b'); w.className = 'who'; w.textContent = m.who; d.appendChild(w); }
    d.appendChild(document.createTextNode(m.body));
    var s = document.createElement('small'); s.textContent = m.day + ', ' + m.time; d.appendChild(s);
    box.appendChild(d); if (m.id > last) last = m.id; down();
  }
  function poll() {
    if (busy || document.hidden) return;
    fetch(API + '?action=poll&event=' + ev + '&with=' + wi + '&after=' + last, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); }).then(function (j) { (j.messages || []).forEach(add); }).catch(function () {});
  }
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var text = input.value.trim(); if (!text) return;
    busy = true;
    var fd = new FormData(form); fd.append('action', 'send');
    fetch(API, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.message) { add(j.message); input.value = ''; } else { alert(j.error || 'Gagal mengirim.'); } })
      .catch(function () { form.submit(); })
      .then(function () { busy = false; input.focus(); });
  });
  down(); setInterval(poll, 3000);
})();
</script>
<?php endif; ?>
@endpush

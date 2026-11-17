(function () {
  'use strict';

  /* ---------- Tema terang / malam ---------- */
  document.querySelectorAll('[data-theme-set]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var t = btn.getAttribute('data-theme-set');
      document.documentElement.setAttribute('data-theme', t);
      try { localStorage.setItem('sk-theme', t); } catch (e) {}
    });
  });

  /* ---------- Filter: kirim form saat pilihan berubah ---------- */
  document.querySelectorAll('select[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });

  /* ---------- Konfirmasi tindakan berisiko ---------- */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (ev) {
      if (!window.confirm(el.getAttribute('data-confirm'))) { ev.preventDefault(); ev.stopImmediatePropagation(); }
    });
  });

  /* ---------- QR Code tiket (pustaka lokal: assets/js/vendor/qrcode.js) ---------- */
  document.querySelectorAll('[data-qr]').forEach(function (el) {
    var text = el.getAttribute('data-qr');
    if (typeof qrcode !== 'function') { el.textContent = text; return; }
    var q = qrcode(0, 'M');
    q.addData(text);
    q.make();
    el.innerHTML = q.createSvgTag({ cellSize: 6, margin: 0, scalable: true, alt: 'QR Code tiket', title: 'QR Code tiket' });
  });

  /* ---------- Menu dropdown (lonceng / akun): tutup saat klik di luar ---------- */
  document.addEventListener('click', function (ev) {
    document.querySelectorAll('details.menu[open]').forEach(function (d) {
      if (!d.contains(ev.target)) { d.removeAttribute('open'); }
    });
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') { document.querySelectorAll('details.menu[open]').forEach(function (d) { d.removeAttribute('open'); }); }
  });
  document.querySelectorAll('details.menu').forEach(function (d) {
    d.addEventListener('toggle', function () {
      if (d.open) { document.querySelectorAll('details.menu[open]').forEach(function (o) { if (o !== d) { o.removeAttribute('open'); } }); }
    });
  });

  /* ---------- Kalender: gulir ke jam sekarang / 08.00 ---------- */
  var sc = document.querySelector('.wk-scroll');
  if (sc) {
    var rowh = parseFloat(getComputedStyle(sc).getPropertyValue('--rowh')) || 52;
    var start = parseFloat(sc.getAttribute('data-start')) || 7;
    var now = sc.querySelector('.wk-now');
    var target = now ? now.offsetTop - 120 : (8 - start) * rowh - 8;
    sc.scrollTop = Math.max(0, target);
  }

  /* ---------- Penanda pesan belum dibaca (lencana) ---------- */
  if (document.body.hasAttribute('data-chat')) {
    var badges = document.querySelectorAll('.js-chat-badge');
    var tick = function () {
      if (document.hidden) { return; }
      fetch(document.body.getAttribute('data-unread-url') || '/pesan/api?action=unread', { credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
          if (!j || typeof j.unread !== 'number') { return; }
          badges.forEach(function (b) { b.textContent = j.unread > 99 ? '99+' : j.unread; b.hidden = j.unread < 1; });
        }).catch(function () {});
    };
    setInterval(tick, 20000);
  }

  /* ---------- Pratinjau nama berkas unggahan ---------- */
  document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
    var img = document.querySelector(inp.getAttribute('data-preview'));
    if (!img) { return; }
    inp.addEventListener('change', function () {
      var f = inp.files && inp.files[0];
      if (!f || !/^image\//.test(f.type)) { img.hidden = true; return; }
      img.src = URL.createObjectURL(f);
      img.hidden = false;
    });
  });
})();

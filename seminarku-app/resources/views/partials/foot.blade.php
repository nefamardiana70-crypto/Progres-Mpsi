<?php /* Skrip tambahan halaman didorong lewat @push('scripts') */ ?>
@stack('scripts')
<script src="<?= e(asset('assets/js/vendor/qrcode.js')) ?>"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>?v=4"></script>
<footer style="text-align: center; padding: 16px 12px; margin-top: 24px; font-size: 13px; color: #64748b; border-top: 1px solid #e2e8f0;">
    <p style="margin: 0 0 4px 0;">
        &copy; 2026 <strong>SeminarKu</strong> &mdash; Tugas Kelompok Manajemen Proyek Sistem Informasi (MPSI)
    </p>
    <p style="margin: 0;">
        Tim Pengembang: Rachmad Fajar Arisyi, Nefa Mardiana, &amp; Sissylia Amanda Putri &bull; Habil Prasetyo, Sistem Informasi UIN Imam Bonjol Padang
    </p>
</footer>
</body>
</html>

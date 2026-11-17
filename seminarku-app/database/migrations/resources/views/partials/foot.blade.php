<?php /* Skrip tambahan halaman didorong lewat @push('scripts') */ ?>
@stack('scripts')
<script src="<?= e(asset('assets/js/vendor/qrcode.js')) ?>"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>?v=4"></script>
</body>
</html>

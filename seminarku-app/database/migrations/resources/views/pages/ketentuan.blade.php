@extends('layouts.public', ['title' => 'Ketentuan Layanan'])

@section('content')
<div class="legal">
    <nav class="legal-toc" aria-label="Isi halaman">
        <?php foreach ($secs as $k => [$t]): ?><a href="#<?= $k ?>"><?= e($t) ?></a><?php endforeach; ?>
    </nav>
    <article class="legal-body">
        <h1>Ketentuan layanan</h1>
        <p class="upd">Berlaku untuk seluruh pengguna <?= APP_NAME ?> · Fakultas Sains dan Teknologi, UIN Imam Bonjol Padang</p>
        <?php foreach ($secs as $k => [$t, $items]): ?>
            <section id="<?= $k ?>">
                <h2><?= e($t) ?></h2>
                <ul><?php foreach ($items as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul>
            </section>
        <?php endforeach; ?>
    </article>
</div>
@endsection

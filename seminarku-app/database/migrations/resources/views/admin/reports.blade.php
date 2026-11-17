@extends('layouts.app', ['title' => 'Laporan Rekapitulasi', 'active' => 'admin_reports'])

@section('content')
<?= stat_tiles([[count($rows), 'Acara tercatat'], [array_sum(array_column($rows, 'appr')), 'Peserta disetujui'], [array_sum(array_column($rows, 'att')), 'Hadir'], [$revenue > 0 ? rupiah($revenue) : 'Rp 0', 'Perkiraan pemasukan panitia']]) ?>
<div class="row-actions" style="margin-bottom:18px"><a class="btn btn-light" href="<?= e(route('admin.reports.export')) ?>"><?= icon('download', 16) ?> Ekspor CSV</a></div>

<div class="panel">
    <h2>Pendaftar per kategori</h2>
    <div style="margin-top:14px;display:flex;flex-direction:column;gap:12px">
    <?php foreach ($cats as $c): ?>
        <div><div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px"><span><?= e($c['category']) ?> <small class="muted">· <?= (int)$c['n'] ?> acara</small></span><b><?= (int)$c['regs'] ?></b></div>
            <div class="meter"><i style="width:<?= round($c['regs'] / $maxRegs * 100) ?>%"></i></div></div>
    <?php endforeach; ?>
    </div>
</div>

<div class="table-wrap"><table class="tbl">
    <thead><tr><th>Acara</th><th>Tanggal</th><th>Pendaftar</th><th>Disetujui</th><th>Hadir</th><th>Kehadiran</th><th>Sertifikat</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><b><?= e($r['title']) ?></b><br><small><?= e($r['organizer']) ?> · <?= e($r['category']) ?></small></td>
            <td><?= e(tgl($r['event_date'])) ?></td>
            <td><?= (int)$r['regs'] ?> / <?= (int)$r['quota'] ?></td>
            <td><?= (int)$r['appr'] ?><?= $r['wait'] ? ' <small class="muted">(+' . (int)$r['wait'] . ' menunggu)</small>' : '' ?></td>
            <td><?= (int)$r['att'] ?></td>
            <td><?= $r['appr'] ? round($r['att'] / max(1, $r['appr']) * 100) . '%' : '-' ?></td>
            <td><?= (int)$r['certs'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
@endsection

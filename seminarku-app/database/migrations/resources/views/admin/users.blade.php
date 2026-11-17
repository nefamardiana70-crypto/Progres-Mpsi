@extends('layouts.app', ['title' => 'Kelola Pengguna', 'active' => 'admin_users'])

@section('content')
<?php $act = route('admin.users.action', array_filter(['role' => $role, 'status' => $status])); ?>
<form class="filters" method="get" action="<?= e(route('admin.users')) ?>">
    <span class="lbl">Filter:</span>
    <?= filter_select('role', 'Peran', ['peserta' => 'Peserta', 'penyelenggara' => 'Penyelenggara', 'admin' => 'Admin'], $role) ?>
    <?= filter_select('status', 'Status', ['aktif' => 'Aktif', 'menunggu' => 'Menunggu validasi', 'nonaktif' => 'Nonaktif'], $status) ?>
    <?php if ($role . $status): ?><a class="btn-reset" href="<?= e(route('admin.users')) ?>">Reset</a><?php endif; ?>
</form>
<div class="table-wrap"><table class="tbl">
    <thead><tr><th>Pengguna</th><th>Peran</th><th>Status</th><th>Bergabung</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r):
        $cls = ['aktif' => 'badge-ok', 'menunggu' => 'badge-warn', 'nonaktif' => 'badge-err'][$r['status']]; ?>
        <tr>
            <td><b><?= e($r['name']) ?></b><br><small><?= e($r['email']) ?><?= $r['nim'] ? ' · ' . e($r['nim']) : '' ?></small></td>
            <td><?= e(role_label($r['role'])) ?></td>
            <td><span class="badge <?= $cls ?>"><?= e(ucfirst($r['status'])) ?></span></td>
            <td><?= e(tgl(substr($r['created_at'], 0, 10))) ?></td>
            <td><?php if ((int)$r['id'] !== (int)$u['id']): ?>
                <form method="post" action="<?= e($act) ?>" class="row-actions"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <?php if ($r['status'] === 'menunggu'): ?><button class="btn btn-sm" name="action" value="validasi">Validasi</button>
                    <?php elseif ($r['status'] === 'aktif'): ?><button class="btn btn-danger btn-sm" name="action" value="nonaktifkan" data-confirm="Nonaktifkan akun ini?">Nonaktifkan</button>
                    <?php else: ?><button class="btn btn-light btn-sm" name="action" value="aktifkan">Aktifkan</button><?php endif; ?>
                    <button class="btn btn-light btn-sm" name="action" value="hapus" title="Hapus akun"
                        data-confirm="Hapus akun <?= e($r['name']) ?> secara permanen? <?= $r['role'] === 'penyelenggara' ? 'Semua acara miliknya beserta pendaftar dan sertifikatnya ikut terhapus. ' : ($r['role'] === 'peserta' ? 'Pendaftaran, sertifikat, dan pesannya ikut terhapus. ' : '') ?>Tindakan ini tidak dapat dibatalkan."><?= icon('x', 14) ?> Hapus</button>
                </form><?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
@endsection

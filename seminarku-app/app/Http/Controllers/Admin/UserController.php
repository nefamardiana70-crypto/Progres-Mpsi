<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kelola pengguna (khusus admin): daftar + filter, validasi panitia, aktif/nonaktif, hapus akun.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = (string)$request->query('role', '');
        $status = (string)$request->query('status', '');

        $q = User::query();
        if (in_array($role, ['peserta', 'penyelenggara', 'admin'], true)) $q->where('role', $role);
        if (in_array($status, ['aktif', 'menunggu', 'nonaktif'], true)) $q->where('status', $status);
        $rows = $q->orderByRaw("(status = 'menunggu') DESC")->orderByDesc('created_at')->orderByDesc('id')->get();

        return view('admin.users', ['u' => user(), 'rows' => $rows, 'role' => $role, 'status' => $status]);
    }

    public function action(Request $request)
    {
        $u = user();
        $target = (int)$request->input('id');
        $action = trim((string)$request->input('action'));
        $back = redirect()->route('admin.users', array_filter(['role' => $request->query('role'), 'status' => $request->query('status')]));

        if ($target === (int)$u['id']) {
            flash('error', 'Anda tidak dapat mengubah akun Anda sendiri.');
            return $back;
        }

        if ($action === 'hapus') {
            $tu = User::find($target);
            if (!$tu) {
                flash('error', 'Pengguna tidak ditemukan.');
            } elseif ($tu->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
                flash('error', 'Admin terakhir tidak dapat dihapus.');
            } else {
                $files = $this->deleteUser($target);
                foreach (array_unique(array_filter($files)) as $f) {
                    delete_upload($f);
                }
                flash('ok', 'Akun ' . $tu->name . ' dan seluruh datanya dihapus.');
            }
            return $back;
        }

        $map = ['validasi' => 'aktif', 'aktifkan' => 'aktif', 'nonaktifkan' => 'nonaktif'];
        if (isset($map[$action]) && User::whereKey($target)->exists()) {
            User::whereKey($target)->update(['status' => $map[$action]]);
            flash('ok', $action === 'validasi' ? 'Penyelenggara divalidasi.' : 'Status akun diperbarui.');
        }
        return $back;
    }

    /**
     * Hapus pengguna beserta seluruh datanya dalam satu transaksi:
     * pesan, sertifikat, pendaftaran (miliknya dan pada acaranya), acara miliknya, lalu akunnya.
     * Mengembalikan daftar berkas unggahan yang harus dihapus dari disk.
     */
    private function deleteUser(int $target): array
    {
        $files = [];
        DB::transaction(function () use ($target, &$files) {
            $evs = DB::table('events')->where('organizer_id', $target)->get(['id', 'qris_image', 'poster']);
            $ids = $evs->pluck('id')->all();
            foreach ($evs as $ev) {
                $files[] = $ev->qris_image;
                $files[] = $ev->poster;
            }
            $regScope = fn($q) => $q->where('user_id', $target)->orWhereIn('event_id', $ids);

            foreach (DB::table('registrations')->whereNotNull('payment_proof')->where($regScope)->pluck('payment_proof') as $f) {
                $files[] = $f;
            }
            DB::table('messages')->where(fn($q) => $q->where('participant_id', $target)->orWhere('sender_id', $target)->orWhereIn('event_id', $ids))->delete();
            $regIds = DB::table('registrations')->where($regScope)->pluck('id')->all();
            DB::table('certificates')->whereIn('registration_id', $regIds)->delete();
            DB::table('registrations')->whereIn('id', $regIds)->delete();
            DB::table('events')->where('organizer_id', $target)->delete();
            DB::table('users')->where('id', $target)->delete();
        });
        return $files;
    }
}

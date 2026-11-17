<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;

/**
 * Acara milik panitia: daftar, buat, ubah, hapus.
 * Penyelenggara hanya mengelola acaranya sendiri; admin boleh semua.
 */
class EventController extends Controller
{
    private function canManage($ev, $u): bool
    {
        return $u['role'] === 'admin' || (int)$ev['organizer_id'] === (int)$u['id'];
    }

    /** Daftar acara (admin: semua acara; penyelenggara: milik sendiri). */
    public function index(Request $request)
    {
        $u = user();
        $today = date('Y-m-d');
        $fStatus = (string)$request->query('status', '');

        $q = Event::withStats();
        if ($u['role'] !== 'admin') {
            $q->where('organizer_id', $u['id']);
        }
        if (in_array($fStatus, ['draft', 'terbit', 'ditutup'], true)) {
            $q->where('status', $fStatus);
        }
        if ($fStatus === 'selesai') {
            $q->where('event_date', '<', $today)->where('status', '<>', 'draft');
        }
        $rows = $q->orderByDesc('event_date')->get();

        return view('organizer.events', [
            'u' => $u,
            'rows' => $rows,
            'fStatus' => $fStatus,
            'title' => $u['role'] === 'admin' ? 'Semua Acara' : 'Acara Saya',
            'sub' => $u['role'] === 'admin' ? 'Seluruh acara dari semua penyelenggara' : 'Kelola acara yang Anda selenggarakan',
        ]);
    }

    /** Hapus acara: hanya bila belum ada pendaftar aktif. Berkas poster, QRIS, dan bukti bayar ikut dibersihkan. */
    public function destroy(Request $request, $id)
    {
        $u = user();
        $ev = get_event((int)$id);
        if (!$ev || !$this->canManage($ev, $u)) {
            flash('error', 'Acara tidak ditemukan.');
        } elseif ((int)$ev['registered'] > 0) {
            flash('error', 'Acara yang sudah memiliki pendaftar tidak dapat dihapus. Tutup pendaftaran sebagai gantinya.');
        } else {
            foreach (Registration::where('event_id', $ev->id)->get() as $r) {   // pendaftar yang ditolak
                delete_upload($r->payment_proof);
            }
            delete_upload($ev['qris_image']);
            delete_upload($ev['poster']);
            Event::whereKey($ev->id)->delete();
            flash('ok', 'Acara dihapus.');
        }
        return redirect()->route('org.events');
    }

    /** Formulir acara baru. */
    public function create()
    {
        return $this->renderForm(null, [
            'title' => '', 'description' => '', 'category' => CATEGORIES[0], 'event_date' => date('Y-m-d', strtotime('+7 days')), 'start_time' => '09:00',
            'reg_deadline' => date('Y-m-d', strtotime('+6 days')), 'location' => '', 'mode' => 'offline', 'fee' => 0, 'quota' => 50, 'has_certificate' => 1,
            'status' => 'terbit', 'approval_mode' => 'manual', 'payment_info' => '', 'qris_image' => null, 'poster' => null, 'registered' => 0,
        ]);
    }

    /** Formulir ubah acara. */
    public function edit($id)
    {
        $ev = $this->findOwned((int)$id);
        if (!$ev) {
            return redirect()->route('org.events');
        }
        return $this->renderForm($ev, $ev->toArray());
    }

    public function store(Request $request)
    {
        return $this->save($request, null);
    }

    public function update(Request $request, $id)
    {
        $ev = $this->findOwned((int)$id);
        if (!$ev) {
            return redirect()->route('org.events');
        }
        return $this->save($request, $ev);
    }

    /** Acara milik pengguna (atau admin); bila tidak, beri pesan dan kembalikan null. */
    private function findOwned(int $id): ?Event
    {
        $ev = get_event($id);
        if (!$ev || !$this->canManage($ev, user())) {
            flash('error', 'Acara tidak ditemukan atau bukan milik Anda.');
            return null;
        }
        return $ev;
    }

    private function renderForm(?Event $ev, array $d, array $conflicts = ['dup' => null, 'venue' => null])
    {
        return view('organizer.event_form', [
            'ev' => $ev,
            'd' => $d,
            'conflicts' => $conflicts,
            'needConfirm' => !empty($conflicts['dup']),
            'today' => date('Y-m-d'),
            'title' => $ev ? 'Ubah Acara' : 'Buat Acara',
            'active' => $ev ? 'org_events' : 'org_new',
        ]);
    }

    /** Validasi dan simpan (acara baru bila $ev null). Bila gagal, formulir ditampilkan lagi dengan isian terakhir. */
    private function save(Request $request, ?Event $ev)
    {
        $u = user();
        $today = date('Y-m-d');
        $in = fn(string $k, string $default = '') => trim((string)$request->input($k, $default));

        $d = $ev ? $ev->toArray() : [
            'title' => '', 'description' => '', 'category' => CATEGORIES[0], 'event_date' => date('Y-m-d', strtotime('+7 days')), 'start_time' => '09:00',
            'reg_deadline' => date('Y-m-d', strtotime('+6 days')), 'location' => '', 'mode' => 'offline', 'fee' => 0, 'quota' => 50, 'has_certificate' => 1,
            'status' => 'terbit', 'approval_mode' => 'manual', 'payment_info' => '', 'qris_image' => null, 'poster' => null, 'registered' => 0,
        ];
        $conflicts = ['dup' => null, 'venue' => null];

        $new = [
            'title' => $in('title'), 'description' => $in('description'), 'category' => $in('category'),
            'event_date' => $in('event_date'), 'start_time' => $in('start_time'), 'reg_deadline' => $in('reg_deadline') ?: $in('event_date'),
            'location' => $in('location'), 'mode' => $in('mode'), 'fee' => max(0, (int)$in('fee', '0')), 'quota' => (int)$in('quota', '0'),
            'has_certificate' => $request->has('has_certificate') ? 1 : 0, 'status' => $in('status'),
            'approval_mode' => $request->has('auto_approve') ? 'auto' : 'manual', 'payment_info' => $in('payment_info'),
        ];

        $err = [];
        if (mb_strlen($new['title']) < 5) $err[] = 'Judul acara minimal 5 karakter.';
        if (mb_strlen($new['description']) < 10) $err[] = 'Deskripsi minimal 10 karakter.';
        if (!in_array($new['category'], CATEGORIES, true)) $err[] = 'Kategori tidak valid.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $new['event_date'])) $err[] = 'Tanggal acara tidak valid.';
        elseif (!$ev && $new['event_date'] < $today) $err[] = 'Tanggal acara tidak boleh di masa lalu.';
        if (!preg_match('/^\d{2}:\d{2}$/', $new['start_time'])) $err[] = 'Jam mulai tidak valid.';
        if ($new['location'] === '') $err[] = 'Lokasi / tautan acara wajib diisi.';
        if (!in_array($new['mode'], ['online', 'offline'], true)) $err[] = 'Mode tidak valid.';
        if (!in_array($new['status'], ['draft', 'terbit', 'ditutup'], true)) $err[] = 'Status tidak valid.';
        if ($new['quota'] < 1) $err[] = 'Kuota minimal 1.';
        if ($ev && $new['quota'] < (int)$ev['registered']) $err[] = 'Kuota tidak boleh lebih kecil dari jumlah pendaftar saat ini (' . $ev['registered'] . ').';
        if ($ev && (int)$ev['registered'] > 0 && $new['fee'] !== (int)$ev['fee']) $err[] = 'Biaya tidak dapat diubah karena sudah ada pendaftar.';
        if (!$err && $new['reg_deadline'] > $new['event_date']) $err[] = 'Batas pendaftaran tidak boleh melewati tanggal acara.';
        if ($new['fee'] > 0) $new['approval_mode'] = 'manual';          // acara berbayar selalu diverifikasi panitia
        if (mb_strlen($new['payment_info']) > 600) $err[] = 'Petunjuk pembayaran maksimal 600 karakter.';

        // Poster: unggah baru / hapus
        $poster = $ev['poster'] ?? null;
        $newPoster = null;
        $upP = save_image_upload('poster', 'poster');
        if (isset($upP['error'])) {
            $err[] = $upP['error'];
        } elseif (isset($upP['file'])) {
            $newPoster = $upP['file'];
        }
        $finalPoster = $newPoster ?? ($request->has('remove_poster') ? null : $poster);

        // QRIS pembayaran: unggah baru / hapus (terpisah dari poster)
        $qris = $ev['qris_image'] ?? null;
        $newQris = null;
        $up = save_image_upload('qris', 'qris');
        if (isset($up['error'])) {
            $err[] = $up['error'];
        } elseif (isset($up['file'])) {
            $newQris = $up['file'];
        }
        $finalQris = $newQris ?? ($request->has('remove_qris') ? null : $qris);
        if ($new['fee'] > 0 && $new['status'] === 'terbit' && !$finalQris && $new['payment_info'] === '') {
            $err[] = 'Acara berbayar wajib mencantumkan gambar QRIS atau petunjuk pembayaran sebelum diterbitkan.';
        }

        // Cegah acara ganda / bentrok tempat
        if (!$err && $new['status'] !== 'draft') {
            $conflicts = find_event_conflicts($new, $ev ? (int)$ev['id'] : null);
            if ($conflicts['venue']) {
                $o = $conflicts['venue'];
                $err[] = 'Lokasi bentrok: "' . $o['title'] . '" (' . $o['organizer_name'] . ') memakai ' . $o['location'] . ' pada ' . tgl($o['event_date']) . ' pukul ' . $o['start_time'] . '. Ubah lokasi atau jam acara Anda.';
            } elseif ($conflicts['dup'] && !$request->has('confirm_similar')) {
                $o = $conflicts['dup'];
                $err[] = 'Acara serupa sudah ada: "' . $o['title'] . '" oleh ' . $o['organizer_name'] . ' (' . tgl($o['event_date']) . '). Centang konfirmasi di bawah jika acara Anda memang berbeda.';
            }
        }

        if ($err) {
            if ($newQris) delete_upload($newQris);
            if ($newPoster) delete_upload($newPoster);
            foreach ($err as $m) flash('error', $m);
            return $this->renderForm($ev, array_merge($d, $new, ['qris_image' => $qris, 'poster' => $poster]), $conflicts);
        }

        // Berkas lama dibersihkan setelah data pasti tersimpan
        if ($newQris || ($ev && !$finalQris && $qris)) delete_upload($qris);
        if ($newPoster || ($ev && !$finalPoster && $poster)) delete_upload($poster);
        $data = $new + ['qris_image' => $finalQris, 'poster' => $finalPoster];

        if ($ev) {
            Event::whereKey($ev->id)->update($data);
            flash('ok', 'Acara diperbarui.');
            return redirect()->route('org.participants', $ev->id);
        }

        Event::create($data + ['organizer_id' => $u['id'], 'presence_code' => (string)random_int(100000, 999999)]);
        flash('ok', $new['status'] === 'terbit' ? 'Acara berhasil dipublikasikan.' : 'Acara disimpan sebagai ' . $new['status'] . '.');
        return redirect()->route('org.events');
    }
}

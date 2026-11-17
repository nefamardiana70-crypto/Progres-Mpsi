<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Peserta satu acara: daftar + filter, ekspor CSV, dan aksi panitia
 * (setujui, tolak, tandai hadir, hapus, setujui semua, kode presensi baru).
 */
class ParticipantController extends Controller
{
    /** Acara milik pengguna (atau admin); bila tidak, beri pesan dan kembalikan null. */
    private function findOwned(int $id): ?Event
    {
        $u = user();
        $ev = get_event($id);
        if (!$ev || ($u['role'] !== 'admin' && (int)$ev['organizer_id'] !== (int)$u['id'])) {
            flash('error', 'Acara tidak ditemukan atau bukan milik Anda.');
            return null;
        }
        return $ev;
    }

    /** Baris peserta (array) lengkap dengan data pengguna dan sertifikat, sesuai filter status. */
    private function rows(Event $ev, string $fStatus): array
    {
        $q = DB::table('registrations as r')
            ->join('users as p', 'p.id', '=', 'r.user_id')
            ->leftJoin('certificates as c', 'c.registration_id', '=', 'r.id')
            ->where('r.event_id', $ev->id)
            ->select('r.*', 'p.name', 'p.nim', 'p.email', 'p.phone', 'c.id as cert_id', 'c.verify_code');
        if (in_array($fStatus, ['menunggu', 'disetujui', 'ditolak'], true)) {
            $q->where('r.status', $fStatus);
        }
        if ($fStatus === 'hadir') {
            $q->where('r.attended', 1);
        }
        if ($fStatus === 'belum') {
            $q->where('r.attended', 0)->where('r.status', 'disetujui');
        }
        return $q->orderBy('r.created_at')->orderBy('r.id')->get()->map(fn($x) => (array)$x)->all();
    }

    public function show(Request $request, $id)
    {
        $u = user();
        $ev = $this->findOwned((int)$id);
        if (!$ev) {
            return redirect()->route('org.events');
        }
        $today = date('Y-m-d');
        $paid = (int)$ev['fee'] > 0;
        $fStatus = (string)$request->query('status', '');

        $cnt = ['menunggu' => 0, 'disetujui' => 0, 'hadir' => 0, 'siap' => 0];
        foreach (Registration::where('event_id', $ev->id)->get(['status', 'attended', 'payment_proof']) as $r) {
            if ($r->status === 'menunggu') {
                $cnt['menunggu']++;
                if (!$paid || $r->payment_proof) $cnt['siap']++;
            }
            if ($r->status === 'disetujui') $cnt['disetujui']++;
            if ($r->attended) $cnt['hadir']++;
        }

        return view('organizer.participants', [
            'u' => $u,
            'ev' => $ev,
            'rows' => $this->rows($ev, $fStatus),
            'fStatus' => $fStatus,
            'cnt' => $cnt,
            'paid' => $paid,
            'canMark' => $ev['status'] !== 'draft' && (string)$ev['event_date'] <= $today,
            'ownerActs' => (int)$ev['organizer_id'] === (int)$u['id'],     // pesan otomatis hanya bila penyelenggara sendiri yang bertindak
        ]);
    }

    /** Rekap peserta (CSV, mengikuti filter status yang sedang dipilih). */
    public function csv(Request $request, $id)
    {
        $ev = $this->findOwned((int)$id);
        if (!$ev) {
            return redirect()->route('org.events');
        }
        $rows = $this->rows($ev, (string)$request->query('status', ''));

        return response()->streamDownload(function () use ($rows, $ev) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF");
            fputcsv($o, ['No', 'Nama', 'NIM', 'Email', 'Telepon', 'Status', 'Hadir', 'Waktu Hadir', 'Kode Sertifikat'], ',', '"', '\\');
            foreach ($rows as $i => $r) {
                fputcsv($o, [$i + 1, $r['name'], $r['nim'], $r['email'], $r['phone'], reg_state($r, $ev)[0], $r['attended'] ? 'Ya' : 'Tidak', $r['attended_at'], $r['verify_code']], ',', '"', '\\');
            }
            fclose($o);
        }, 'peserta-acara-' . $ev->id . '.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    /** Semua aksi POST pada halaman peserta. */
    public function action(Request $request, $id)
    {
        $u = user();
        $ev = $this->findOwned((int)$id);
        if (!$ev) {
            return redirect()->route('org.events');
        }
        $today = date('Y-m-d');
        $paid = (int)$ev['fee'] > 0;
        $ownerActs = (int)$ev['organizer_id'] === (int)$u['id'];
        $action = trim((string)$request->input('action'));

        // Pesan otomatis ke peserta lewat Pesan (hanya bila penyelenggara sendiri yang bertindak)
        $notify = function (int $participantId, string $text) use ($ev, $u, $ownerActs) {
            if ($ownerActs) {
                chat_send((int)$ev->id, $participantId, (int)$u['id'], $text);
            }
        };
        $approvedText = 'Pendaftaran Anda untuk "' . $ev['title'] . '" telah disetujui. QR tiket sudah tersedia di menu Tiket Saya.';

        if ($action === 'kode_baru') {
            Event::whereKey($ev->id)->update(['presence_code' => (string)random_int(100000, 999999)]);
            flash('ok', 'Kode presensi baru dibuat.');
        } elseif ($action === 'setujui_semua') {
            $n = 0;
            foreach (Registration::where('event_id', $ev->id)->where('status', 'menunggu')->get() as $r) {
                if ($paid && empty($r->payment_proof)) continue;       // acara berbayar: wajib ada bukti
                $r->update(['status' => 'disetujui', 'reject_reason' => null]);
                $notify((int)$r->user_id, $approvedText);
                $n++;
            }
            flash($n ? 'ok' : 'info', $n ? $n . ' pendaftaran disetujui.' : 'Tidak ada pendaftaran yang siap disetujui' . ($paid ? ' (peserta belum mengunggah bukti pembayaran).' : '.'));
        } else {
            $reg = Registration::where('id', (int)$request->input('reg'))->where('event_id', $ev->id)->first();
            if (!$reg) {
                flash('error', 'Data peserta tidak ditemukan.');
            } elseif ($action === 'setujui') {
                if ($paid && empty($reg->payment_proof) && $request->input('manual') !== '1') {
                    flash('error', 'Peserta belum mengunggah bukti pembayaran.');
                } else {
                    $reg->update(['status' => 'disetujui', 'reject_reason' => null]);
                    $notify((int)$reg->user_id, $approvedText);
                    flash('ok', 'Pendaftaran disetujui. QR tiket peserta kini tampil.');
                }
            } elseif ($action === 'tolak') {
                if ($reg->attended) {
                    flash('error', 'Peserta yang sudah hadir tidak dapat ditolak.');
                } else {
                    $reason = mb_substr(trim((string)$request->input('reason')), 0, 250);
                    $reg->update(['status' => 'ditolak', 'reject_reason' => $reason ?: null]);
                    $notify((int)$reg->user_id, 'Pendaftaran Anda untuk "' . $ev['title'] . '" belum dapat kami setujui' . ($reason ? ': ' . $reason : '.')
                        . ($paid ? ' Anda dapat mengunggah ulang bukti pembayaran di halaman tiket.' : ''));
                    flash('ok', 'Pendaftaran ditolak.');
                }
            } elseif ($action === 'hadir') {
                if ($reg->status !== 'disetujui') flash('error', 'Hanya pendaftar yang sudah disetujui yang dapat dicatat hadir.');
                elseif ($ev['status'] === 'draft') flash('error', 'Acara belum dipublikasikan.');
                elseif ((string)$ev['event_date'] > $today) flash('error', 'Presensi baru dibuka pada hari acara (' . tgl($ev['event_date'], true) . ').');
                else {
                    $c = mark_attended((int)$reg->id);
                    flash('ok', $c ? 'Kehadiran dicatat dan e-sertifikat diterbitkan.' : 'Kehadiran dicatat.');
                }
            } elseif ($action === 'hapus') {
                if ($reg->attended) {
                    flash('error', 'Peserta yang sudah hadir tidak dapat dihapus.');
                } else {
                    delete_upload($reg->payment_proof);
                    $reg->delete();
                    flash('ok', 'Peserta dihapus dari acara.');
                }
            }
        }
        return redirect()->route('org.participants', $ev->id);
    }
}

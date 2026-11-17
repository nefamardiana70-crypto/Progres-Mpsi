<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;

/**
 * Presensi: pemindai QR tiket atau input kode manual. POST mengembalikan JSON.
 */
class ScanController extends Controller
{
    /** Acara terbit milik pengguna (admin: semua), diurutkan dari yang tanggalnya paling dekat dengan hari ini. */
    private function scanEvents($u)
    {
        $today = date('Y-m-d');
        $q = Event::where('status', 'terbit');
        if ($u['role'] !== 'admin') {
            $q->where('organizer_id', $u['id']);
        }
        return $q->get(['id', 'title', 'event_date', 'status'])->sort(function ($a, $b) use ($today) {
            $da = abs((strtotime((string)$a['event_date']) - strtotime($today)) / 86400);
            $db = abs((strtotime((string)$b['event_date']) - strtotime($today)) / 86400);
            return [$da, $b['event_date']] <=> [$db, $a['event_date']];
        })->values();
    }

    public function index(Request $request)
    {
        $evs = $this->scanEvents(user());
        $ids = $evs->pluck('id')->map(fn($i) => (int)$i)->all();
        return view('organizer.scan', [
            'evs' => $evs,
            'selected' => (int)$request->query('event', $ids[0] ?? 0),
        ]);
    }

    /** Catat hadir berdasarkan kode tiket (dipanggil lewat fetch dari pemindai atau input manual). */
    public function checkin(Request $request)
    {
        $today = date('Y-m-d');
        $ids = $this->scanEvents(user())->pluck('id')->map(fn($i) => (int)$i)->all();
        $eventId = (int)$request->input('event');
        $code = strtoupper(trim((string)$request->input('code')));

        if (!in_array($eventId, $ids, true)) {
            return response()->json(['ok' => false, 'msg' => 'Pilih acara yang valid.']);
        }
        $ev = get_event($eventId);
        if ((string)$ev['event_date'] > $today) {
            return response()->json(['ok' => false, 'msg' => 'Presensi baru dibuka pada hari acara (' . tgl($ev['event_date'], true) . ').']);
        }

        $reg = Registration::with('user')->where('ticket_code', $code)->where('event_id', $eventId)->first();
        if (!$reg) {
            return response()->json(['ok' => false, 'msg' => 'Kode tiket tidak ditemukan pada acara ini.']);
        }
        $name = $reg->user->name;
        if ($reg->status !== 'disetujui') {
            return response()->json(['ok' => false, 'msg' => 'Tiket belum disetujui panitia, tidak dapat dipakai presensi.', 'name' => $name]);
        }
        if ($reg->attended) {
            return response()->json(['ok' => true, 'msg' => 'Sudah tercatat hadir sebelumnya (' . substr((string)$reg->attended_at, 11, 5) . ').', 'name' => $name]);
        }
        $cert = mark_attended((int)$reg->id);
        return response()->json(['ok' => true, 'msg' => $cert ? 'Hadir tercatat. E-sertifikat diterbitkan.' : 'Hadir tercatat.', 'name' => $name]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pesan peserta <-> panitia, per acara. PRIVAT: admin tidak punya akses.
 * Halaman (index/send) hanya untuk peserta dan penyelenggara; api() melayani polling JSON.
 */
class ChatController extends Controller
{
    /** Daftar percakapan milik pengguna, terbaru di atas. */
    private function conversations($u, int $eventId, bool $isOrg): array
    {
        $me = (int)$u['id'];
        $pairs = [];   // "event:peserta" => [event_id, participant_id, judul, nama lawan bicara]

        if ($isOrg) {
            $sqls = [
                "SELECT DISTINCT e.id AS eid, e.title, p.id AS pid, p.name FROM events e JOIN registrations r ON r.event_id = e.id JOIN users p ON p.id = r.user_id WHERE e.organizer_id = ?",
                "SELECT DISTINCT e.id AS eid, e.title, p.id AS pid, p.name FROM events e JOIN messages m ON m.event_id = e.id JOIN users p ON p.id = m.participant_id WHERE e.organizer_id = ?",
            ];
            foreach ($sqls as $sql) {
                foreach (DB::select($sql, [$me]) as $r) {
                    $pairs[$r->eid . ':' . $r->pid] = [(int)$r->eid, (int)$r->pid, $r->title, $r->name];
                }
            }
        } else {
            $rows = DB::select("SELECT DISTINCT e.id AS eid, e.title, o.name FROM events e JOIN users o ON o.id = e.organizer_id
                WHERE e.status <> 'draft' AND (EXISTS (SELECT 1 FROM registrations r WHERE r.event_id = e.id AND r.user_id = ?)
                                             OR EXISTS (SELECT 1 FROM messages m WHERE m.event_id = e.id AND m.participant_id = ?))", [$me, $me]);
            foreach ($rows as $r) {
                $pairs[$r->eid . ':' . $me] = [(int)$r->eid, $me, $r->title, $r->name];
            }
            // peserta membuka obrolan dari halaman acara yang belum didaftar
            if ($eventId && !isset($pairs[$eventId . ':' . $me])) {
                $c = chat_context($u, $eventId, 0);
                if ($c) {
                    $pairs[$eventId . ':' . $me] = [$eventId, $me, $c[0]['title'], $c[0]['organizer_name']];
                }
            }
        }

        $convs = [];
        foreach ($pairs as [$eid, $pid, $title, $name]) {
            $m = Message::where('event_id', $eid)->where('participant_id', $pid)->orderByDesc('id')->first();
            $unread = Message::where('event_id', $eid)->where('participant_id', $pid)->where('sender_id', '<>', $me)->whereNull('read_at')->count();
            $convs[] = [
                'eid' => $eid, 'pid' => $pid, 'title' => $title, 'name' => $name, 'org' => '',
                'last' => $m ? ['body' => $m->body, 'created_at' => (string)$m->created_at, 'sender_id' => (int)$m->sender_id] : null,
                'unread' => $unread,
            ];
        }
        usort($convs, fn($a, $b) => strcmp($b['last']['created_at'] ?? '0', $a['last']['created_at'] ?? '0'));
        return $convs;
    }

    public function index(Request $request)
    {
        $u = user();
        $me = (int)$u['id'];
        $isOrg = $u['role'] !== 'peserta';          // sisi panitia memilih peserta
        $eventId = (int)$request->query('event', 0);
        $withId = (int)$request->query('with', $request->query('user', 0));

        $convs = $this->conversations($u, $eventId, $isOrg);

        // Percakapan aktif
        $cur = null;
        foreach ($convs as $c) {
            if ($c['eid'] === $eventId && ($isOrg ? $c['pid'] === $withId : true)) {
                $cur = $c;
                break;
            }
        }
        if (!$cur && !$eventId && $convs) {
            $cur = $convs[0];   // buka yang terbaru
        }

        $msgs = [];
        $lastId = 0;
        if ($cur) {
            $msgs = DB::table('messages as m')->join('users as s', 's.id', '=', 'm.sender_id')
                ->where('m.event_id', $cur['eid'])->where('m.participant_id', $cur['pid'])
                ->orderBy('m.id')->get(['m.*', 's.name as sname', 's.role as srole'])->map(fn($x) => (array)$x)->all();
            $lastId = $msgs ? (int)end($msgs)['id'] : 0;
            Message::where('event_id', $cur['eid'])->where('participant_id', $cur['pid'])
                ->where('sender_id', '<>', $me)->whereNull('read_at')->update(['read_at' => date('Y-m-d H:i:s')]);
            foreach ($convs as $i => $c) {
                if ($c['eid'] === $cur['eid'] && $c['pid'] === $cur['pid']) {
                    $convs[$i]['unread'] = 0;
                }
            }
        }

        return view('chat', [
            'u' => $u, 'me' => $me, 'isOrg' => $isOrg, 'convs' => $convs, 'cur' => $cur, 'msgs' => $msgs, 'lastId' => $lastId,
            'title' => 'Pesan', 'active' => 'chat',
            'sub' => $isOrg ? 'Komunikasi langsung dengan peserta acara Anda' : 'Tanya langsung kepada panitia, tanpa aplikasi pihak ketiga',
        ]);
    }

    /** Kirim pesan (tanpa JavaScript). */
    public function send(Request $request)
    {
        $u = user();
        $isOrg = $u['role'] !== 'peserta';
        $ctx = chat_context($u, (int)$request->input('event'), (int)$request->input('with'));
        if (!$ctx) {
            flash('error', 'Percakapan tidak ditemukan.');
            return redirect()->route('chat.index');
        }
        if (!chat_send((int)$ctx[0]['id'], $ctx[1], (int)$u['id'], (string)$request->input('body'))) {
            flash('error', 'Pesan kosong atau lebih dari 1000 karakter.');
        }
        return redirect()->route('chat.index', ['event' => $ctx[0]['id']] + ($isOrg ? ['with' => $ctx[1]] : []));
    }

    /**
     * Titik akhir JSON (polling dan penanda pesan baru).
     *  GET  ?action=unread
     *  GET  ?action=poll&event=E&with=P&after=ID   -> pesan baru + tandai terbaca
     *  POST action=send&event=E&with=P&body=...    (token CSRF lewat _token)
     */
    public function api(Request $request)
    {
        $u = user();
        if (!$u || $u->status === 'nonaktif') {
            return response()->json(['error' => 'Sesi berakhir. Silakan masuk kembali.'], 401);
        }
        $action = (string)$request->input('action', '');
        if ($action === 'unread') {
            return response()->json(['unread' => unread_messages((int)$u['id'])]);
        }

        $ctx = chat_context($u, (int)$request->input('event', 0), (int)$request->input('with', 0));
        if (!$ctx) {
            return response()->json(['error' => 'Percakapan tidak ditemukan.'], 404);
        }
        [$ev, $pid] = $ctx;

        // Nama pengirim ditampilkan hanya bila pengirimnya admin (label "Admin"); pesan sendiri tanpa label.
        $roles = [];
        $fmt = function ($m) use ($u, &$roles) {
            $sid = (int)$m->sender_id;
            if (!isset($roles[$sid])) {
                $roles[$sid] = DB::table('users')->where('id', $sid)->value('role');
            }
            $ts = strtotime((string)$m->created_at);
            return [
                'who' => $sid === (int)$u['id'] ? '' : ($roles[$sid] === 'admin' ? 'Admin' : ''),
                'id' => (int)$m->id,
                'mine' => $sid === (int)$u['id'],
                'body' => $m->body,
                'time' => date('H:i', $ts),
                'day' => date('j', $ts) . ' ' . mb_substr(BULAN[(int)date('n', $ts)], 0, 3),
            ];
        };

        if ($action === 'poll') {
            $rows = Message::where('event_id', $ev->id)->where('participant_id', $pid)->where('id', '>', (int)$request->query('after', 0))->orderBy('id')->get();
            if ($rows->isNotEmpty()) {
                Message::where('event_id', $ev->id)->where('participant_id', $pid)->where('sender_id', '<>', $u['id'])->whereNull('read_at')
                    ->update(['read_at' => date('Y-m-d H:i:s')]);
            }
            return response()->json(['messages' => $rows->map($fmt)->values()]);
        }

        if ($action === 'send' && $request->isMethod('post')) {
            $m = chat_send((int)$ev->id, $pid, (int)$u['id'], (string)$request->input('body', ''));
            if (!$m) {
                return response()->json(['error' => 'Pesan kosong atau lebih dari 1000 karakter.'], 422);
            }
            return response()->json(['message' => $fmt($m)]);
        }

        return response()->json(['error' => 'Permintaan tidak valid.'], 400);
    }
}

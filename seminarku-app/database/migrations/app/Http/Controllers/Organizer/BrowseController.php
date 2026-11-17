<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Jelajah Acara: seluruh acara (selain draf) dari semua penyelenggara.
 * Tujuannya agar panitia dapat melihat acara yang sudah/akan terlaksana sebelum membuat acara baru.
 */
class BrowseController extends Controller
{
    public function index(Request $request)
    {
        $u = user();
        $today = date('Y-m-d');
        $when = (string)$request->query('waktu', 'datang');
        $org = (int)$request->query('penyelenggara', 0);
        $cat = (string)$request->query('kategori', '');
        $q = trim((string)$request->query('q', ''));

        $query = Event::withStats()->where('status', '<>', 'draft');
        if ($when === 'lalu') $query->where('event_date', '<', $today);
        elseif ($when === 'datang') $query->where('event_date', '>=', $today);
        if ($org) $query->where('organizer_id', $org);
        if (in_array($cat, CATEGORIES, true)) $query->where('category', $cat);
        if ($q !== '') {
            $like = '%' . $q . '%';
            $query->where(fn($w) => $w->where('title', 'like', $like)->orWhere('location', 'like', $like)->orWhere('description', 'like', $like));
        }
        $rows = $query->orderBy('event_date', $when === 'datang' ? 'asc' : 'desc')->orderBy('start_time')->get();

        $orgOpts = [];
        foreach (DB::table('users as u')->join('events as e', 'e.organizer_id', '=', 'u.id')->where('e.status', '<>', 'draft')
                     ->distinct()->orderBy('u.name')->get(['u.id', 'u.name']) as $o) {
            $orgOpts[$o->id] = $o->name;
        }

        return view('organizer.browse', [
            'u' => $u, 'rows' => $rows, 'when' => $when, 'org' => $org, 'cat' => $cat, 'q' => $q,
            'orgOpts' => $orgOpts, 'filtered' => ($when !== 'datang') || $org || $cat || $q,
        ]);
    }

    /** Titik akhir ringan untuk formulir acara: acara lain pada tanggal tertentu (JSON). */
    public function check(Request $request)
    {
        $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$request->query('date', '')) ? (string)$request->query('date') : '';
        $events = [];
        if ($d) {
            $events = DB::table('events as e')->join('users as o', 'o.id', '=', 'e.organizer_id')
                ->where('e.event_date', $d)->where('e.status', '<>', 'draft')->where('e.id', '<>', (int)$request->query('exclude', 0))
                ->orderBy('e.start_time')->get(['e.title', 'e.start_time', 'e.location', 'e.mode', 'o.name as organizer']);
        }
        return response()->json(['events' => $events]);
    }
}

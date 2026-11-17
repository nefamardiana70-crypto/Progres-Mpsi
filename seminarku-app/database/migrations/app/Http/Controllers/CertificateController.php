<?php

namespace App\Http\Controllers;

use App\Services\CertificatePdf;
use Illuminate\Support\Facades\DB;

/** Sertifikat peserta: daftar dan unduh PDF. */
class CertificateController extends Controller
{
    /** Daftar sertifikat milik peserta. */
    public function index()
    {
        $rows = DB::table('certificates as c')
            ->join('registrations as r', 'r.id', '=', 'c.registration_id')
            ->join('events as e', 'e.id', '=', 'r.event_id')
            ->join('users as o', 'o.id', '=', 'e.organizer_id')
            ->where('r.user_id', user()->id)
            ->orderByDesc('c.issued_at')
            ->get(['c.id as cert_id', 'c.verify_code', 'c.issued_at', 'c.holder_name', 'e.*', 'o.name as organizer_name'])
            ->map(fn($x) => (array)$x)->all();

        return view('pages.certificates', compact('rows'));
    }

    /** Unduh/tampilkan PDF: hanya pemilik, penyelenggara acara tersebut, atau admin. */
    public function pdf(int $id)
    {
        $u = user();
        $row = DB::table('certificates as c')
            ->join('registrations as r', 'r.id', '=', 'c.registration_id')
            ->join('events as e', 'e.id', '=', 'r.event_id')
            ->join('users as o', 'o.id', '=', 'e.organizer_id')
            ->join('users as p', 'p.id', '=', 'r.user_id')
            ->where('c.id', $id)
            ->first(['c.*', 'r.user_id', 'e.title', 'e.category', 'e.event_date', 'e.location', 'e.organizer_id', 'o.name as organizer_name', 'p.nim']);
        $c = $row ? (array)$row : null;

        $allowed = $c && ((int)$c['user_id'] === (int)$u['id'] || (int)$c['organizer_id'] === (int)$u['id'] || $u['role'] === 'admin');
        if (!$allowed) {
            abort(404, 'Sertifikat tidak ditemukan.');
        }

        $c['verify_url'] = route('verify', ['code' => $c['verify_code']]);
        $pdf = CertificatePdf::build($c);
        $fname = 'Sertifikat-' . preg_replace('/[^A-Za-z0-9]+/', '-', $c['title']) . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fname . '"',
            'Content-Length' => strlen($pdf),
        ]);
    }
}

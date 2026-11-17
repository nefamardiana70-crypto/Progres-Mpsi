<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Laporan rekapitulasi (khusus admin). Pendaftar yang ditolak tidak dihitung;
 * perkiraan pemasukan = biaya x jumlah pendaftar yang disetujui.
 */
class ReportController extends Controller
{
    /** Satu baris per acara non-draf, lengkap dengan hitungan pendaftar. */
    private function eventRows(): array
    {
        $rows = DB::select("SELECT e.id, e.title, e.category, e.event_date, e.quota, e.status, e.fee, o.name AS organizer,
                COUNT(r.id) AS regs, COALESCE(SUM(CASE WHEN r.status = 'disetujui' THEN 1 ELSE 0 END),0) AS appr,
                COALESCE(SUM(CASE WHEN r.status = 'menunggu' THEN 1 ELSE 0 END),0) AS wait,
                COALESCE(SUM(r.attended),0) AS att,
                (SELECT COUNT(*) FROM certificates c JOIN registrations r2 ON r2.id = c.registration_id WHERE r2.event_id = e.id) AS certs
            FROM events e JOIN users o ON o.id = e.organizer_id LEFT JOIN registrations r ON r.event_id = e.id AND r.status <> 'ditolak'
            WHERE e.status <> 'draft' GROUP BY e.id, e.title, e.category, e.event_date, e.quota, e.status, e.fee, o.name ORDER BY e.event_date DESC");
        return array_map(fn($r) => (array)$r, $rows);
    }

    public function index()
    {
        $rows = $this->eventRows();
        $revenue = array_sum(array_map(fn($r) => (int)$r['fee'] * (int)$r['appr'], $rows));
        $cats = array_map(fn($r) => (array)$r, DB::select("SELECT e.category, COUNT(DISTINCT e.id) AS n, COUNT(r.id) AS regs
            FROM events e LEFT JOIN registrations r ON r.event_id = e.id AND r.status <> 'ditolak'
            WHERE e.status != 'draft' GROUP BY e.category ORDER BY regs DESC"));
        $maxRegs = max(1, ...array_map('intval', array_column($cats, 'regs') ?: [1]));

        return view('admin.reports', ['rows' => $rows, 'revenue' => $revenue, 'cats' => $cats, 'maxRegs' => $maxRegs]);
    }

    /** Ekspor CSV rekapitulasi. */
    public function export()
    {
        $rows = $this->eventRows();
        return response()->streamDownload(function () use ($rows) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF");
            fputcsv($o, ['Acara', 'Kategori', 'Penyelenggara', 'Tanggal', 'Kuota', 'Pendaftar', 'Disetujui', 'Hadir', 'Sertifikat', 'Perkiraan pemasukan penyelenggara'], ',', '"', '\\');
            foreach ($rows as $r) {
                fputcsv($o, [$r['title'], $r['category'], $r['organizer'], $r['event_date'], $r['quota'], $r['regs'], $r['appr'], $r['att'], $r['certs'], (int)$r['fee'] * (int)$r['appr']], ',', '"', '\\');
            }
            fclose($o);
        }, 'laporan-rekapitulasi.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}

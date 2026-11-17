<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;

/**
 * Penyaji gambar unggahan (poster, QRIS, bukti bayar). Berkas disimpan di storage dan tidak dapat diakses langsung.
 *  poster : publik untuk acara terbit; acara draf hanya untuk pemilik dan admin
 *  qris   : admin, penyelenggara acara, atau peserta yang terdaftar pada acara itu
 *  proof  : pemilik pendaftaran, penyelenggara acara, atau admin
 */
class FileController extends Controller
{
    private function deny(int $code = 404)
    {
        return response($code === 403 ? 'Tidak diizinkan.' : 'Berkas tidak ditemukan.', $code)->header('Content-Type', 'text/plain; charset=utf-8');
    }

    public function show(Request $request, string $type, $id)
    {
        $u = user();
        $id = (int)$id;
        if (!$u && $type !== 'poster') {
            return $this->deny(403);
        }
        $rel = null;
        $cache = 'private, max-age=300';

        if ($type === 'poster') {
            $ev = get_event($id);
            if (!$ev || !$ev['poster']) {
                return $this->deny();
            }
            if ($ev['status'] === 'draft' && !($u && ($u['role'] === 'admin' || (int)$ev['organizer_id'] === (int)$u['id']))) {
                return $this->deny(403);
            }
            $rel = $ev['poster'];
            $cache = $ev['status'] === 'draft' ? 'private, max-age=300' : 'public, max-age=3600';
        } elseif ($type === 'qris') {
            $ev = get_event($id);
            if (!$ev || !$ev['qris_image']) {
                return $this->deny();
            }
            $ok = $u['role'] === 'admin' || (int)$ev['organizer_id'] === (int)$u['id'];
            if (!$ok && $u['role'] === 'peserta' && $ev['status'] !== 'draft') {
                $ok = Registration::where('event_id', $id)->where('user_id', $u['id'])->exists();
            }
            if (!$ok) {
                return $this->deny(403);
            }
            $rel = $ev['qris_image'];
        } elseif ($type === 'proof') {
            $r = Registration::with('event:id,organizer_id')->find($id);
            if (!$r || !$r->payment_proof) {
                return $this->deny();
            }
            if ($u['role'] !== 'admin' && (int)$r->user_id !== (int)$u['id'] && (int)$r->event->organizer_id !== (int)$u['id']) {
                return $this->deny(403);
            }
            $rel = $r->payment_proof;
        } else {
            return $this->deny();
        }

        $path = upload_path($rel);
        if (!$path) {
            return $this->deny();
        }
        $mime = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][pathinfo($path, PATHINFO_EXTENSION)] ?? 'application/octet-stream';
        return response()->file($path, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $cache,
        ]);
    }
}

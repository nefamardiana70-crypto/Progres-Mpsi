<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Registration;

/** Tiket peserta: daftar tiket, rincian + QR, unggah bukti bayar, presensi kode, pembatalan. */
class TicketController extends Controller
{
    /** Ambil pendaftaran milik peserta yang sedang masuk, atau null. */
    private function mine(int $id): ?Registration
    {
        return Registration::where('id', $id)->where('user_id', user()->id)->first();
    }

    private function missing()
    {
        flash('error', 'Tiket tidak ditemukan.');
        return redirect()->route('tickets.index');
    }

    /** Daftar semua tiket milik peserta. */
    public function index()
    {
        $mine = Registration::query()->join('events', 'events.id', '=', 'registrations.event_id')
            ->where('registrations.user_id', user()->id)
            ->orderByDesc('events.event_date')->orderBy('events.start_time')
            ->get(['registrations.id as reg_id', 'registrations.status as reg_status', 'registrations.attended', 'registrations.payment_proof', 'events.id as event_id']);

        return view('pages.tickets.index', compact('mine'));
    }

    /** Rincian satu tiket: stepper status, pembayaran, QR (hanya bila disetujui), presensi kode. */
    public function show(int $id)
    {
        $reg = $this->mine($id);
        if (!$reg) {
            return $this->missing();
        }
        $ev = get_event((int)$reg['event_id']);
        $today = date('Y-m-d');
        $paid = (int)$ev['fee'] > 0;
        $cert = Certificate::where('registration_id', $reg['id'])->first();
        [$lbl, $cls] = reg_state($reg, $ev);
        $approved = $reg['status'] === 'disetujui';
        $rejected = $reg['status'] === 'ditolak';
        $needPay = $paid && !$approved && !$rejected;
        $qrOk = ticket_qr_visible($reg, $ev);
        $tone = ((int)$ev['id'] % 2) ? 'maroon' : 'army';
        $t = strtotime((string)$ev['event_date']);

        // Langkah-langkah (stepper)
        $steps = [['Mendaftar', 'done']];
        if ($paid) {
            $steps[] = ['Bayar', $reg['payment_proof'] || $approved ? 'done' : ($rejected ? 'bad' : 'now')];
        }
        $steps[] = [$rejected ? 'Ditolak' : 'Disetujui', $approved ? 'done' : ($rejected ? 'bad' : (($paid && !$reg['payment_proof']) ? '' : 'now'))];
        $steps[] = ['Hadir', $reg['attended'] ? 'done' : ($approved ? 'now' : '')];
        if ($ev['has_certificate']) {
            $steps[] = ['Sertifikat', $cert ? 'done' : ''];
        }

        return view('pages.tickets.show', compact('reg', 'ev', 'today', 'paid', 'cert', 'lbl', 'cls', 'approved', 'rejected', 'needPay', 'qrOk', 'tone', 't', 'steps'));
    }

    /** Unggah bukti pembayaran (acara berbayar, status menunggu). */
    public function proof(int $id)
    {
        $reg = $this->mine($id);
        if (!$reg) {
            return $this->missing();
        }
        $ev = get_event((int)$reg['event_id']);
        if ((int)$ev['fee'] <= 0 || $reg['status'] !== 'menunggu') {
            flash('error', 'Bukti pembayaran tidak dapat diunggah pada status ini.');
        } elseif (event_done($ev)) {
            flash('error', 'Acara sudah berlalu.');
        } else {
            $up = save_image_upload('proof', 'proof');
            if (isset($up['error'])) {
                flash('error', $up['error']);
            } elseif (!isset($up['file'])) {
                flash('error', 'Pilih gambar bukti pembayaran terlebih dahulu.');
            } else {
                delete_upload($reg['payment_proof']);
                $reg->update(['payment_proof' => $up['file'], 'proof_at' => date('Y-m-d H:i:s')]);
                flash('ok', 'Bukti pembayaran terkirim. Panitia akan memeriksanya dan menyetujui pendaftaran Anda.');
            }
        }
        return redirect()->route('tickets.show', $reg['id']);
    }

    /** Presensi mandiri dengan kode 6 digit pada hari pelaksanaan. */
    public function presence(int $id)
    {
        $reg = $this->mine($id);
        if (!$reg) {
            return $this->missing();
        }
        $ev = get_event((int)$reg['event_id']);
        $code = trim((string)request()->input('code', ''));
        if ($reg['attended']) {
            flash('info', 'Kehadiran Anda sudah tercatat.');
        } elseif ($reg['status'] !== 'disetujui') {
            flash('error', 'Pendaftaran Anda belum disetujui panitia.');
        } elseif ((string)$ev['event_date'] !== date('Y-m-d')) {
            flash('error', 'Presensi hanya dapat dilakukan pada hari pelaksanaan acara.');
        } elseif (!hash_equals((string)$ev['presence_code'], $code)) {
            flash('error', 'Kode presensi salah.');
        } else {
            $cert = mark_attended((int)$reg['id']);
            flash('ok', $cert ? 'Kehadiran tercatat. E-sertifikat Anda sudah terbit.' : 'Kehadiran Anda tercatat.');
        }
        return redirect()->route('tickets.show', $reg['id']);
    }

    /** Batalkan pendaftaran (tidak bisa bila sudah hadir). */
    public function cancel(int $id)
    {
        $reg = $this->mine($id);
        if (!$reg) {
            return $this->missing();
        }
        if ($reg['attended']) {
            flash('error', 'Pendaftaran tidak dapat dibatalkan karena Anda sudah hadir.');
            return redirect()->route('tickets.show', $reg['id']);
        }
        delete_upload($reg['payment_proof']);
        $reg->delete();
        flash('ok', 'Pendaftaran dibatalkan.');
        return redirect()->route('tickets.index');
    }
}

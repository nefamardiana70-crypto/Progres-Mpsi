<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh (demo). Tanggal acara RELATIF terhadap hari ini supaya data selalu segar.
 * Jalankan: php artisan migrate:fresh --seed
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seed = require __DIR__ . '/data.php';
        $now = date('Y-m-d H:i:s');
        $day = fn(int $n): string => date('Y-m-d', strtotime(sprintf('%+d days', $n)));
        $hex = fn(int $n): string => strtoupper(bin2hex(random_bytes($n)));
        $proof = 'proof/' . md5('seminarku-contoh-bukti-transfer') . '.png';

        DB::transaction(function () use ($seed, $now, $day, $hex, $proof) {
            foreach ($seed['users'] as $i => $x) {
                DB::table('users')->insert([
                    'id' => $i + 1, 'name' => $x[0], 'email' => $x[1], 'password' => Hash::make($x[2]), 'role' => $x[3],
                    'nim' => $x[4], 'institution' => $x[5], 'phone' => $x[6], 'status' => $x[7], 'created_at' => $now,
                ]);
            }

            foreach ($seed['events'] as $i => $x) {
                $date = $day($x[4]);
                DB::table('events')->insert([
                    'id' => $i + 1, 'organizer_id' => $x[0], 'title' => $x[1], 'description' => $x[2], 'category' => $x[3],
                    'event_date' => $date, 'start_time' => $x[5], 'reg_deadline' => $date, 'location' => $x[6], 'mode' => $x[7],
                    'fee' => $x[8], 'quota' => $x[9], 'has_certificate' => $x[10], 'status' => $x[11],
                    'presence_code' => (string)random_int(100000, 999999), 'approval_mode' => $x[12], 'payment_info' => $x[13],
                    'qris_image' => null, 'poster' => null, 'created_at' => $now,
                ]);
            }

            foreach ($seed['regs'] as $i => $x) {
                [$ev, $user, $status, $hasProof, $att, $reason, $off] = $x;
                $created = $day($off) . ' 10:00:00';
                $eventDate = $day($seed['events'][$ev - 1][4]);
                $attAt = $att ? $eventDate . ' 09:' . sprintf('%02d', random_int(5, 40)) . ':00' : null;
                DB::table('registrations')->insert([
                    'id' => $i + 1, 'event_id' => $ev, 'user_id' => $user, 'ticket_code' => 'TKT-' . $hex(5), 'status' => $status,
                    'payment_proof' => $hasProof ? $proof : null, 'proof_at' => $hasProof ? $created : null, 'reject_reason' => $reason,
                    'attended' => $att ? 1 : 0, 'attended_at' => $attAt, 'created_at' => $created,
                ]);
                if ($att && $seed['events'][$ev - 1][10]) {
                    DB::table('certificates')->insert([
                        'registration_id' => $i + 1, 'verify_code' => 'CERT-' . $hex(5),
                        'holder_name' => $seed['users'][$user - 1][0], 'issued_at' => $attAt,
                    ]);
                }
            }

            foreach ($seed['messages'] as $x) {
                $t = date('Y-m-d H:i:s', time() - $x[4] * 60);
                DB::table('messages')->insert([
                    'event_id' => $x[0], 'participant_id' => $x[1], 'sender_id' => $x[2], 'body' => $x[3],
                    'created_at' => $t, 'read_at' => $x[5] ? $t : null,
                ]);
            }
        });

        $this->makeSampleProof($proof);
    }

    /** Gambar contoh bukti transfer untuk data demo. */
    private function makeSampleProof(string $rel): void
    {
        $disk = Storage::disk('local');
        if ($disk->exists('uploads/' . $rel)) {
            return;
        }
        if (function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor(260, 360);
            imagefill($im, 0, 0, imagecolorallocate($im, 250, 246, 246));
            $ink = imagecolorallocate($im, 70, 40, 46);
            imagefilledrectangle($im, 0, 0, 259, 44, imagecolorallocate($im, 127, 29, 45));
            imagestring($im, 4, 18, 14, 'BUKTI TRANSFER (CONTOH)', imagecolorallocate($im, 255, 255, 255));
            foreach (['Nominal   Rp 25.000', 'Tujuan    Bank BSI', 'Status    BERHASIL', 'Ref       SK-DEMO-0001'] as $i => $line) {
                imagestring($im, 3, 18, 72 + $i * 26, $line, $ink);
            }
            imagestring($im, 2, 18, 320, 'Gambar ini hanya contoh data demo.', imagecolorallocate($im, 140, 120, 124));
            ob_start();
            imagepng($im);
            $png = (string)ob_get_clean();
            imagedestroy($im);
        } else {
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        }
        $disk->put('uploads/' . $rel, $png);
    }
}

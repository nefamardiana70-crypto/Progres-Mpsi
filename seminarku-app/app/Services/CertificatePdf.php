<?php

namespace App\Services;

/**
 * Pembuat PDF minimal (tanpa pustaka luar): satu halaman, font standar Helvetica.
 * Cukup untuk sertifikat teks + bentuk dasar. Koordinat dalam poin, titik asal di kiri-atas.
 */
final class MiniPdf
{
    public float $w;
    public float $h;
    private string $c = '';
    private const FONTS = ['F1' => 'Helvetica', 'F2' => 'Helvetica-Bold', 'F3' => 'Helvetica-Oblique'];

    // Lebar karakter ASCII 32..126 (per 1000 em)
    private const W_REG = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584];
    private const W_BOLD = [278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584];

    public function __construct(bool $landscape = true)
    {
        $this->w = $landscape ? 841.89 : 595.28;
        $this->h = $landscape ? 595.28 : 841.89;
    }

    private static function rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return sprintf('%.3F %.3F %.3F', hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255);
    }

    public function rect(float $x, float $y, float $w, float $h, ?string $fill = null, ?string $stroke = null, float $lw = 1): void
    {
        $op = ($fill && $stroke) ? 'B' : ($fill ? 'f' : 'S');
        $s = '';
        if ($fill) $s .= self::rgb($fill) . " rg\n";
        if ($stroke) $s .= self::rgb($stroke) . " RG\n" . sprintf('%.2F w', $lw) . "\n";
        $this->c .= $s . sprintf("%.2F %.2F %.2F %.2F re %s\n", $x, $this->h - $y - $h, $w, $h, $op);
    }

    public function line(float $x1, float $y1, float $x2, float $y2, string $color = '#000000', float $lw = 1): void
    {
        $this->c .= self::rgb($color) . " RG\n" . sprintf("%.2F w\n%.2F %.2F m %.2F %.2F l S\n", $lw, $x1, $this->h - $y1, $x2, $this->h - $y2);
    }

    public function width(string $text, string $font, float $size): float
    {
        $table = $font === 'F2' ? self::W_BOLD : self::W_REG;
        $w = 0;
        $text = $this->enc($text);
        for ($i = 0, $n = strlen($text); $i < $n; $i++) {
            $o = ord($text[$i]);
            $w += ($o >= 32 && $o <= 126) ? $table[$o - 32] : 556;
        }
        return $w * $size / 1000;
    }

    public function text(float $x, float $y, string $text, string $font = 'F1', float $size = 12, string $color = '#000000'): void
    {
        $this->c .= sprintf("BT /%s %.2F Tf %s rg %.2F %.2F Td (%s) Tj ET\n", $font, $size, self::rgb($color), $x, $this->h - $y, $this->esc($this->enc($text)));
    }

    /** Teks rata tengah pada x tengah $cx; ukuran otomatis dikecilkan agar muat dalam $maxW. */
    public function center(float $cx, float $y, string $text, string $font, float $size, string $color, float $maxW = 0): void
    {
        if ($maxW > 0) {
            while ($size > 8 && $this->width($text, $font, $size) > $maxW) {
                $size -= 1;
            }
        }
        $this->text($cx - $this->width($text, $font, $size) / 2, $y, $text, $font, $size, $color);
    }

    /** Pecah teks menjadi beberapa baris sesuai lebar maksimum. */
    public function wrap(string $text, string $font, float $size, float $maxW): array
    {
        $lines = [];
        $cur = '';
        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $try = $cur === '' ? $word : $cur . ' ' . $word;
            if ($cur !== '' && $this->width($try, $font, $size) > $maxW) {
                $lines[] = $cur;
                $cur = $word;
            } else {
                $cur = $try;
            }
        }
        if ($cur !== '') $lines[] = $cur;
        return $lines;
    }

    private function enc(string $s): string
    {
        $r = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
        return $r === false ? preg_replace('/[^\x20-\x7E]/', '?', $s) : $r;
    }

    private function esc(string $s): string
    {
        return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $s);
    }

    public function output(string $title = 'Dokumen'): string
    {
        $objs = [];
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objs[3] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R /F3 7 0 R >> >> >>', $this->w, $this->h);
        $objs[4] = '<< /Length ' . strlen($this->c) . " >>\nstream\n" . $this->c . "endstream";
        $i = 5;
        foreach (self::FONTS as $name) {
            $objs[$i++] = '<< /Type /Font /Subtype /Type1 /BaseFont /' . $name . ' /Encoding /WinAnsiEncoding >>';
        }
        $objs[$i] = '<< /Title (' . $this->esc($this->enc($title)) . ') /Producer (SeminarKu) >>';
        $infoId = $i;

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $n => $body) {
            $offsets[$n] = strlen($pdf);
            $pdf .= $n . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= 'trailer << /Size ' . (count($objs) + 1) . ' /Root 1 0 R /Info ' . $infoId . " 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }
}

/** Gambar sertifikat kehadiran A4 lanskap. */
final class CertificatePdf
{
    /**
     * @param array $c title, category, event_date, location, organizer_name, holder_name, nim, verify_code, verify_url, issued_at
     */
    public static function build(array $c): string
    {
        $p = new MiniPdf(true);
        $W = $p->w;
        $H = $p->h;
        $cx = $W / 2;
        $purple = '#7f1d2d';
        $soft = '#f6e4e8';

        $p->rect(0, 0, $W, $H, '#fffdfb');
        $p->rect(22, 22, $W - 44, $H - 44, null, $purple, 5);
        $p->rect(36, 36, $W - 72, $H - 72, null, '#d9b8be', 1);
        // Hiasan sudut
        foreach ([[22, 22], [$W - 22 - 70, 22], [22, $H - 22 - 14], [$W - 22 - 70, $H - 22 - 14]] as [$x, $y]) {
            $p->rect($x, $y, 70, 14, $purple);
        }
        $p->rect($cx - 60, 22, 120, 14, '#4b5d27');

        $p->center($cx, 100, 'SERTIFIKAT', 'F2', 46, $purple);
        $p->center($cx, 124, 'KEHADIRAN ' . strtoupper($c['category']), 'F1', 12, '#8b88a6');
        $p->line($cx - 70, 138, $cx + 70, 138, '#4b5d27', 2);

        $p->center($cx, 178, 'Diberikan kepada', 'F3', 14, '#6c6a85');
        $p->center($cx, 228, $c['holder_name'], 'F2', 34, '#24213f', $W - 200);
        $nameW = min($W - 200, max(260, $p->width($c['holder_name'], 'F2', 30)));
        $p->line($cx - $nameW / 2, 240, $cx + $nameW / 2, 240, '#7f1d2d', 1.2);
        if (!empty($c['nim'])) {
            $p->center($cx, 260, 'NIM ' . $c['nim'], 'F1', 11, '#8b88a6');
        }

        $p->center($cx, 292, 'atas partisipasinya sebagai peserta dalam kegiatan', 'F3', 13, '#6c6a85');
        $y = 326;
        foreach (array_slice($p->wrap($c['title'], 'F2', 22, $W - 220), 0, 2) as $line) {
            $p->center($cx, $y, $line, 'F2', 22, $purple);
            $y += 28;
        }
        $info = 'yang diselenggarakan oleh ' . $c['organizer_name'] . ' pada ' . \tgl($c['event_date'], true) . ' di ' . $c['location'];
        $y += 4;
        foreach (array_slice($p->wrap($info, 'F1', 12, $W - 240), 0, 2) as $line) {
            $p->center($cx, $y, $line, 'F1', 12, '#4a4766');
            $y += 17;
        }

        // Kaki: kode verifikasi (kiri) dan penyelenggara (kanan)
        $p->rect(64, $H - 126, 300, 58, $soft);
        $p->text(78, $H - 106, 'KODE VERIFIKASI', 'F2', 8.5, '#8b88a6');
        $p->text(78, $H - 88, $c['verify_code'], 'F2', 16, $purple);
        $p->text(78, $H - 75, 'Cek keaslian: ' . $c['verify_url'], 'F1', 6.5, '#6c6a85');

        $sx = $W - 64 - 200;
        $p->center($sx + 100, $H - 112, 'Penyelenggara', 'F1', 10, '#8b88a6');
        $p->line($sx, $H - 88, $sx + 200, $H - 88, '#24213f', 0.8);
        $p->center($sx + 100, $H - 74, $c['organizer_name'], 'F2', 11, '#24213f', 200);
        $p->center($cx + 20, $H - 52, 'Diterbitkan secara elektronik pada ' . \tgl(substr((string)$c['issued_at'], 0, 10), true), 'F3', 8, '#8b88a6');

        return $p->output('Sertifikat - ' . $c['holder_name']);
    }
}

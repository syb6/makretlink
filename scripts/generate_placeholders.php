<?php

/**
 * MarketLink placeholder image generator (pure PHP, no GD, no packages).
 *
 * Bakes deterministic, themed PNG art for each category/market placeholder.
 * Run: php scripts/generate_placeholders.php
 *
 * Design: layered soft gradients + translucent organic blobs + a leaf
 * motif + vignette, in olive/cream tones harmonising with the theme.
 */

$palette = [
    'vegetables'      => [[46, 92, 47],  [156, 189, 84]],
    'fruits'          => [[168, 92, 26], [240, 176, 74]],
    'dairy-eggs'      => [[92, 118, 88], [232, 226, 205]],
    'baked-goods'     => [[122, 82, 38], [222, 184, 118]],
    'herbs-greens'    => [[34, 84, 52],  [140, 190, 96]],
    'honey-preserves' => [[146, 96, 20], [238, 190, 70]],
    'market'          => [[64, 84, 44],  [190, 200, 120]],
    'general'         => [[70, 92, 54],  [176, 196, 120]],
];

$targets = [
    'general'          => [640, 640],
    'vegetables'       => [640, 640],
    'fruits'           => [640, 640],
    'dairy-eggs'       => [640, 640],
    'baked-goods'      => [640, 640],
    'herbs-greens'     => [640, 640],
    'honey-preserves'  => [640, 640],
    'market'           => [800, 500],
];

$outDir = __DIR__ . '/../public/images/placeholders';

function crc32Table(): array
{
    static $table = null;
    if ($table !== null) {
        return $table;
    }
    $table = [];
    for ($n = 0; $n < 256; $n++) {
        $c = $n;
        for ($k = 0; $k < 8; $k++) {
            $c = ($c & 1) ? (0xEDB88320 ^ ($c >> 1)) : ($c >> 1);
        }
        $table[$n] = $c;
    }

    return $table;
}

function pngChunk(string $type, string $data): string
{
    $table = crc32Table();
    $crcInput = $type . $data;
    $crc = 0xFFFFFFFF;
    foreach (str_split($crcInput) as $ch) {
        $crc = $table[($crc ^ ord($ch)) & 0xFF] ^ ($crc >> 8);
    }
    $crc = ($crc ^ 0xFFFFFFFF) & 0xFFFFFFFF;

    return pack('N', strlen($data)) . $crcInput . pack('N', $crc);
}

function writePng(string $path, int $w, int $h, string $pixels): void
{
    $raw = '';
    $stride = $w * 4;
    for ($y = 0; $y < $h; $y++) {
        $raw .= "\x00" . substr($pixels, $y * $stride, $stride);
    }

    $png  = "\x89PNG\r\n\x1a\n";
    $png .= pngChunk('IHDR', pack('N2C5', $w, $h, 8, 6, 0, 0, 0));
    $png .= pngChunk('IDAT', zlib_encode($raw, ZLIB_ENCODING_DEFLATE, 6));
    $png .= pngChunk('IEND', '');

    file_put_contents($path, $png);
}

/** Blend $fg over $bg using alpha 0-255. */
function blend(int $br, int $bgc, int $bb, int $fr, int $fgc, int $fb, int $alpha): array
{
    $a = $alpha / 255;

    return [
        (int) round($br * (1 - $a) + $fr * $a),
        (int) round($bgc * (1 - $a) + $fgc * $a),
        (int) round($bb * (1 - $a) + $fb * $a),
    ];
}

foreach ($targets as $key => [$w, $h]) {
    [$c1, $c2] = $palette[$key];
    mt_srand(crc32($key));

    $px = str_repeat("\0", $w * $h * 4);

    // Layer 1: diagonal two-stop gradient (dark top-left -> light bottom-right).
    for ($y = 0; $y < $h; $y++) {
        $row = '';
        for ($x = 0; $x < $w; $x++) {
            $t = ($x / $w * 0.45) + ($y / $h * 0.55);
            $row .= pack('C4',
                (int) round($c1[0] + ($c2[0] - $c1[0]) * $t),
                (int) round($c1[1] + ($c2[1] - $c1[1]) * $t),
                (int) round($c1[2] + ($c2[2] - $c1[2]) * $t),
                255
            );
        }
        $px = substr_replace($px, $row, $y * $w * 4, $w * 4);
    }

    // Layer 2: translucent organic blobs (deterministic per key).
    $blobs = [];
    for ($i = 0; $i < 9; $i++) {
        $blobs[] = [
            mt_rand((int) ($w * 0.05), (int) ($w * 0.95)),
            mt_rand((int) ($h * 0.05), (int) ($h * 0.95)),
            mt_rand((int) ($w * 0.08), (int) ($w * 0.30)),
            mt_rand(18, 52),
            $i % 2 === 0 ? [255, 255, 255] : [20, 40, 18],
        ];
    }

    $buf = unpack('C*', $px);
    foreach ($blobs as [$cx, $cy, $rad, $alpha, $rgb]) {
        $r2 = $rad * $rad;
        for ($y = max(0, $cy - $rad); $y < min($h, $cy + $rad); $y++) {
            $dy = $y - $cy;
            for ($x = max(0, $cx - $rad); $x < min($w, $cx + $rad); $x++) {
                $dx = $x - $cx;
                $d2 = $dx * $dx + $dy * $dy;
                if ($d2 > $r2) {
                    continue;
                }
                $edge = 1.0 - sqrt($d2 / $r2);
                $a = (int) round($alpha * min(1.0, $edge * 2.2));
                if ($a <= 0) {
                    continue;
                }
                $i4 = (($y * $w) + $x) * 4 + 1; // unpack is 1-based
                [$br, $bgc, $bb] = blend($buf[$i4], $buf[$i4 + 1], $buf[$i4 + 2], $rgb[0], $rgb[1], $rgb[2], $a);
                $buf[$i4] = $br; $buf[$i4 + 1] = $bgc; $buf[$i4 + 2] = $bb;
            }
        }
    }

    // Layer 3: leaf motif — a rotated soft ellipse pair in cream.
    $leafAlpha = 70;
    $angle = deg2rad(mt_rand(-40, 40));
    $ca = cos($angle); $sa = sin($angle);
    $lw = $w * 0.16; $lh = $w * 0.055;
    $lcx = (int) ($w * 0.5); $lcy = (int) ($h * 0.46);
    for ($y = max(0, $lcy - (int) $lw); $y < min($h, $lcy + (int) $lw); $y++) {
        for ($x = max(0, $lcx - (int) $lw); $x < min($w, $lcx + (int) $lw); $x++) {
            $dx = $x - $lcx; $dy = $y - $lcy;
            $rx = $dx * $ca + $dy * $sa;
            $ry = -$dx * $sa + $dy * $ca;
            $v = ($rx * $rx) / ($lw * $lw) + ($ry * $ry) / ($lh * $lh);
            if ($v > 1.0) {
                continue;
            }
            $a = (int) round($leafAlpha * (1.0 - $v));
            if ($a <= 0) {
                continue;
            }
            $i4 = (($y * $w) + $x) * 4 + 1;
            [$br, $bgc, $bb] = blend($buf[$i4], $buf[$i4 + 1], $buf[$i4 + 2], 250, 246, 226, $a);
            $buf[$i4] = $br; $buf[$i4 + 1] = $bgc; $buf[$i4 + 2] = $bb;
        }
    }

    // Layer 4: vignette (darker corners) for depth.
    $maxD = sqrt($w * $w + $h * $h) / 2;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $dx = ($x - $w / 2) / $maxD;
            $dy = ($y - $h / 2) / $maxD;
            $d = sqrt($dx * $dx + $dy * $dy);
            $a = (int) round(60 * max(0.0, $d - 0.55) / 0.45);
            if ($a <= 0) {
                continue;
            }
            $i4 = (($y * $w) + $x) * 4 + 1;
            [$br, $bgc, $bb] = blend($buf[$i4], $buf[$i4 + 1], $buf[$i4 + 2], 16, 24, 14, $a);
            $buf[$i4] = $br; $buf[$i4 + 1] = $bgc; $buf[$i4 + 2] = $bb;
        }
    }

    $px = pack('C*', ...$buf);
    writePng("{$outDir}/{$key}.png", $w, $h, $px);
    echo "wrote {$key}.png ({$w}x{$h})\n";
}

echo "DONE\n";

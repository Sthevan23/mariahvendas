<?php

namespace App\Services;

/**
 * Descobre a cor principal do vestido numa foto e escolhe qual das cores do produto ela é.
 * Ignora o fundo (cor das bordas da foto) e olha só o miolo, onde fica o vestido.
 */
class PhotoColor
{
    /** @return array{0:int,1:int,2:int}|null cor média do vestido (R, G, B) */
    public static function dominantRgb(string $file): ?array
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $data = @file_get_contents($file);
        $src = $data !== false ? @imagecreatefromstring($data) : false;
        if (!$src) {
            return null;
        }
        $w0 = imagesx($src);
        $h0 = imagesy($src);
        $w = 80;
        $h = max(1, (int) round($h0 * $w / max(1, $w0)));
        $img = imagecreatetruecolor($w, $h);
        imagecopyresampled($img, $src, 0, 0, 0, 0, $w, $h, $w0, $h0);
        imagedestroy($src);

        $px = static function (int $x, int $y) use ($img): array {
            $c = imagecolorat($img, $x, $y);
            return [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
        };

        // Cor do fundo: média das bordas esquerda e direita.
        $bg = [0, 0, 0];
        $n = 0;
        for ($y = 0; $y < $h; $y += 2) {
            foreach ([1, 2, $w - 3, $w - 2] as $x) {
                $p = $px($x, $y);
                $bg[0] += $p[0];
                $bg[1] += $p[1];
                $bg[2] += $p[2];
                $n++;
            }
        }
        $bg = [$bg[0] / $n, $bg[1] / $n, $bg[2] / $n];

        // Miolo da foto, do pescoço para baixo (evita rosto e cabelo).
        $sum = [0, 0, 0];
        $count = 0;
        for ($y = (int) ($h * 0.30); $y < (int) ($h * 0.92); $y++) {
            for ($x = (int) ($w * 0.25); $x < (int) ($w * 0.75); $x++) {
                $p = $px($x, $y);
                $dist = sqrt(($p[0] - $bg[0]) ** 2 + ($p[1] - $bg[1]) ** 2 + ($p[2] - $bg[2]) ** 2);
                if ($dist < 38) {
                    continue; // parece fundo
                }
                $sum[0] += $p[0];
                $sum[1] += $p[1];
                $sum[2] += $p[2];
                $count++;
            }
        }
        imagedestroy($img);
        if ($count < 40) {
            return null;
        }
        return [(int) round($sum[0] / $count), (int) round($sum[1] / $count), (int) round($sum[2] / $count)];
    }

    /** Distância de cor perceptual simples (CIE Lab). */
    public static function distance(array $a, array $b): float
    {
        [$l1, $a1, $b1] = self::lab($a);
        [$l2, $a2, $b2] = self::lab($b);
        return sqrt(($l1 - $l2) ** 2 + ($a1 - $a2) ** 2 + ($b1 - $b2) ** 2);
    }

    public static function hexToRgb(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * @param array<string,string> $colors nome da cor => hex
     * @return array{0:string,1:float,2:float}|null [cor mais parecida, distância, folga para a 2ª colocada]
     */
    public static function nearest(array $rgb, array $colors): ?array
    {
        $ranked = [];
        foreach ($colors as $name => $hex) {
            $target = self::hexToRgb((string) $hex);
            if ($target) {
                $ranked[$name] = self::distance($rgb, $target);
            }
        }
        if (!$ranked) {
            return null;
        }
        asort($ranked);
        $names = array_keys($ranked);
        $best = $ranked[$names[0]];
        $second = $ranked[$names[1]] ?? ($best + 100);
        return [$names[0], $best, $second - $best];
    }

    private static function lab(array $rgb): array
    {
        $f = static function (float $c): float {
            $c /= 255;
            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };
        [$r, $g, $b] = [$f($rgb[0]), $f($rgb[1]), $f($rgb[2])];
        $x = ($r * 0.4124 + $g * 0.3576 + $b * 0.1805) / 0.95047;
        $y = $r * 0.2126 + $g * 0.7152 + $b * 0.0722;
        $z = ($r * 0.0193 + $g * 0.1192 + $b * 0.9505) / 1.08883;
        $t = static fn(float $v): float => $v > 0.008856 ? $v ** (1 / 3) : 7.787 * $v + 16 / 116;
        [$fx, $fy, $fz] = [$t($x), $t($y), $t($z)];
        return [116 * $fy - 16, 500 * ($fx - $fy), 200 * ($fy - $fz)];
    }
}

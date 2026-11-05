<?php

/**
 * Gera hero em 2560px (JPEG + WebP) a partir de public/uploads/moda/hero-atelier.jpg
 */
function mariah_rebuild_hero_assets(string $root): bool
{
    $src = $root . '/public/uploads/moda/hero-atelier.jpg';
    if (!is_file($src)) {
        return false;
    }

    $img = @imagecreatefromjpeg($src);
    if ($img === false) {
        return false;
    }

    $sw = imagesx($img);
    $sh = imagesy($img);
    $tw = 2560;
    $th = (int) round($sh * ($tw / max(1, $sw)));

    $out = imagecreatetruecolor($tw, $th);
    imagecopyresampled($out, $img, 0, 0, 0, 0, $tw, $th, $sw, $sh);
    imagedestroy($img);

    $jpg = $root . '/public/uploads/moda/hero-atelier.jpg';
    imagejpeg($out, $jpg, 92);

    if (function_exists('imagewebp')) {
        imagewebp($out, $root . '/public/uploads/moda/hero-atelier.webp', 88);
    }

    imagedestroy($out);

    return true;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    $root = dirname(__DIR__);
    if (!mariah_rebuild_hero_assets($root)) {
        fwrite(STDERR, "Falha ao gerar hero.\n");
        exit(1);
    }
    echo "OK hero-atelier.jpg\n";
}

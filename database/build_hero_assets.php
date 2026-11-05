<?php

/**
 * Gera hero em 2560px (JPEG + WebP) a partir de public/uploads/moda/hero-atelier.jpg
 */
$root = dirname(__DIR__);
$src = $root . '/public/uploads/moda/hero-atelier.jpg';
if (!is_file($src)) {
    fwrite(STDERR, "Arquivo não encontrado: {$src}\n");
    exit(1);
}

$img = @imagecreatefromjpeg($src);
if ($img === false) {
    fwrite(STDERR, "JPEG inválido: {$src}\n");
    exit(1);
}

$sw = imagesx($img);
$sh = imagesy($img);
$tw = 2560;
$th = (int) round($sh * ($tw / $sw));

$out = imagecreatetruecolor($tw, $th);
imagecopyresampled($out, $img, 0, 0, 0, 0, $tw, $th, $sw, $sh);
imagedestroy($img);

$jpg = $root . '/public/uploads/moda/hero-atelier.jpg';
imagejpeg($out, $jpg, 92);

$webpPath = null;
if (function_exists('imagewebp')) {
    $webpPath = $root . '/public/uploads/moda/hero-atelier.webp';
    imagewebp($out, $webpPath, 88);
}

imagedestroy($out);

echo "OK {$tw}x{$th}\n";
echo basename($jpg) . ' ' . filesize($jpg) . " bytes\n";
if ($webpPath && is_file($webpPath)) {
    echo basename($webpPath) . ' ' . filesize($webpPath) . " bytes\n";
}

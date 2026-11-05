<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$dir = dirname(__DIR__) . '/public/uploads/products/';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$sources = [
    'vestido-midi-areia' => [
        'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1581044777550-4cfa60707c03?auto=format&fit=crop&w=900&q=80',
    ],
    'blusa-cetim-nude' => [
        'https://images.unsplash.com/photo-1564257631407-4deb1f99d992?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?auto=format&fit=crop&w=900&q=80',
    ],
    'calca-wide-leg-creme' => [
        'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?auto=format&fit=crop&w=900&q=80',
    ],
    'conjunto-linho-natural' => [
        'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=900&q=80',
    ],
    'saia-midi-plissada' => [
        'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?auto=format&fit=crop&w=900&q=80',
    ],
    'jeans-mom-lavagem-clara' => [
        'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=900&q=80',
    ],
    'camisa-oversized-off' => [
        'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=900&q=80',
    ],
    'vestido-longo-preto' => [
        'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?auto=format&fit=crop&w=900&q=80',
    ],
];

$categoryImages = [
    'vestidos' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&w=800&q=80',
    'blusas' => 'https://images.unsplash.com/photo-1564257631407-4deb1f99d992?auto=format&fit=crop&w=800&q=80',
    'calcas' => 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?auto=format&fit=crop&w=800&q=80',
    'conjuntos' => 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=800&q=80',
    'saias' => 'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?auto=format&fit=crop&w=800&q=80',
    'jeans' => 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=800&q=80',
    'acessorios' => 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80',
    'promocoes' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=800&q=80',
    'novidades' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=800&q=80',
];

function download_image(string $url, string $dest): bool
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: MandallaBot/1.0\r\nAccept: image/*\r\n",
            'follow_location' => 1,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 1000) {
        return false;
    }
    return file_put_contents($dest, $data) !== false;
}

$find = $pdo->prepare('SELECT id, name FROM products WHERE slug = ?');
$del = $pdo->prepare('DELETE FROM product_images WHERE product_id = ?');
$ins = $pdo->prepare('INSERT INTO product_images (product_id, path, alt, sort_order) VALUES (?, ?, ?, ?)');

foreach ($sources as $slug => $urls) {
    $find->execute([$slug]);
    $product = $find->fetch();
    if (!$product) {
        echo "skip product $slug\n";
        continue;
    }
    $del->execute([$product['id']]);
    foreach ($urls as $i => $url) {
        $filename = $slug . '-' . $i . '.jpg';
        $path = $dir . $filename;
        if (!download_image($url, $path)) {
            echo "FAIL download $url\n";
            continue;
        }
        $ins->execute([(int) $product['id'], '/uploads/products/' . $filename, $product['name'], $i]);
        echo "ok {$product['name']} #$i\n";
    }
}

$updCat = $pdo->prepare('UPDATE categories SET image = ? WHERE slug = ?');
foreach ($categoryImages as $slug => $url) {
    $filename = 'cat-' . $slug . '.jpg';
    $path = $dir . $filename;
    if (!download_image($url, $path)) {
        echo "FAIL cat $slug\n";
        continue;
    }
    $updCat->execute(['/uploads/products/' . $filename, $slug]);
    echo "ok category $slug\n";
}

$heroUrl = 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1600&q=80';
$heroFile = $dir . 'hero.jpg';
if (download_image($heroUrl, $heroFile)) {
    $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'hero_image'")
        ->execute(['/uploads/products/hero.jpg']);
    echo "ok hero\n";
}

$feedUrls = [
    'https://images.unsplash.com/photo-1581044777550-4cfa60707c03?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?auto=format&fit=crop&w=600&q=80',
];
$feedLocal = [];
foreach ($feedUrls as $i => $url) {
    $filename = 'ig-' . $i . '.jpg';
    if (download_image($url, $dir . $filename)) {
        $feedLocal[] = '/uploads/products/' . $filename;
        echo "ok ig $i\n";
    }
}
if ($feedLocal) {
    $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'instagram_feed'")
        ->execute([json_encode($feedLocal, JSON_UNESCAPED_SLASHES)]);
}

echo "done\n";

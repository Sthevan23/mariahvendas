<?php

/**
 * Importa a Coleção Bali (fotos extraídas dos PDFs) como produtos da loja.
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Core\Helpers;

$pdo = Database::connection();
$manifestPath = dirname(__DIR__) . '/public/uploads/products/bali/manifest.json';
if (!file_exists($manifestPath)) {
    fwrite(STDERR, "Rode antes: python database/extract_bali.py\n");
    exit(1);
}

$manifest = json_decode(file_get_contents($manifestPath), true);
if (!$manifest) {
    fwrite(STDERR, "Manifest inválido\n");
    exit(1);
}

// Categoria Coleção Bali
$slugCat = 'colecao-bali';
$stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
$stmt->execute([$slugCat]);
$catId = $stmt->fetchColumn();

$cover = '/uploads/products/bali/bali-001.jpg';
if (!$catId) {
    $pdo->prepare(
        'INSERT INTO categories (name, slug, image, description, is_active, sort_order) VALUES (?, ?, ?, ?, 1, 0)'
    )->execute([
        'Coleção Bali',
        $slugCat,
        $cover,
        'Coleção Bali — Verão 27. Leveza, texturas artesanais e tons neutros.',
    ]);
    $catId = (int) $pdo->lastInsertId();
    echo "Categoria criada #$catId\n";
} else {
    $pdo->prepare('UPDATE categories SET name = ?, image = ?, description = ?, is_active = 1 WHERE id = ?')
        ->execute([
            'Coleção Bali',
            $cover,
            'Coleção Bali — Verão 27. Leveza, texturas artesanais e tons neutros.',
            $catId,
        ]);
    echo "Categoria atualizada #$catId\n";
}

// Também vínculo em Novidades image
$pdo->prepare("UPDATE categories SET image = ? WHERE slug = 'novidades'")->execute([$cover]);

$names = [
    'Conjunto Bali Off-White',
    'Vestido Strapless Bali',
    'Conjunto Peplum Dourado',
    'Saia Midi Bali Natural',
    'Top Cropped Linho Bali',
    'Vestido Curto Verão 27',
    'Conjunto Textura Artesanal',
    'Blusa Ombro a Ombro Bali',
    'Calça Wide Bali Creme',
    'Vestido Longo Brisa',
    'Conjunto Botões Ouro',
    'Saia Flores Bali',
    'Macacão Leve Mandalla',
    'Blusa Cetim Areia Bali',
    'Shorts Linho Tropical',
    'Vestido Midi Neutro',
    'Conjunto Resort Wear',
    'Top Alça Fina Bali',
    'Saia Plissada Verão',
    'Vestido Envelope Bali',
];

$colors = [
    ['Off-white', '#F5F0E8'],
    ['Areia', '#C4A484'],
    ['Creme', '#E8DFD4'],
    ['Natural', '#D4C4B0'],
    ['Branco', '#FFFFFF'],
];
$sizes = ['P', 'M', 'G', 'GG'];

// Remove produtos Bali anteriores para reimportar limpo
$old = $pdo->query("SELECT id FROM products WHERE slug LIKE 'bali-look-%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($old as $oid) {
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$oid]);
}
echo 'Removidos ' . count($old) . " produtos Bali antigos\n";

$insProduct = $pdo->prepare(
    'INSERT INTO products (category_id, name, slug, description, price, compare_at_price, is_active, is_featured, is_new, is_sale, is_bestseller, cost_price, sold_count)
     VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
);
$insImage = $pdo->prepare('INSERT INTO product_images (product_id, path, alt, sort_order) VALUES (?, ?, ?, ?)');
$insVariant = $pdo->prepare(
    'INSERT INTO product_variants (product_id, size, color, color_hex, sku, stock) VALUES (?, ?, ?, ?, ?, ?)'
);

$created = 0;
$lookNum = 0;

foreach ($manifest as $item) {
    $idx = (int) $item['index'];
    $path = $item['file'];
    $w = (int) $item['w'];
    $h = (int) $item['h'];

    // Capa / editorial tipográfico
    if ($idx <= 2) {
        echo "skip capa/editorial #$idx\n";
        continue;
    }
    // Paisagens horizontais (menos ideais pra card)
    if ($w > $h * 1.15) {
        echo "skip landscape #$idx\n";
        continue;
    }

    $lookNum++;
    $nameBase = $names[($lookNum - 1) % count($names)];
    $name = $nameBase . ' ' . str_pad((string) $lookNum, 2, '0', STR_PAD_LEFT);
    $slug = 'bali-look-' . str_pad((string) $lookNum, 3, '0', STR_PAD_LEFT);

    $price = [189.9, 219.9, 249.9, 279.9, 299.9, 329.9, 359.9][($lookNum - 1) % 7];
    $sale = $lookNum % 5 === 0;
    $compare = $sale ? round($price * 1.18, 2) : null;
    $featured = $lookNum <= 12;
    $best = $lookNum % 4 === 0;
    $cost = round($price * 0.42, 2);

    $desc = "Peça da Coleção Bali — Verão 27. Inspirada em texturas artesanais, fibras naturais e tons neutros. "
        . "Look {$lookNum} da campanha Mandalla. (Foto oficial da coleção.)";

    $insProduct->execute([
        $catId,
        $name,
        $slug,
        $desc,
        $price,
        $compare,
        $featured ? 1 : 0,
        1, // is_new
        $sale ? 1 : 0,
        $best ? 1 : 0,
        $cost,
        $best ? rand(5, 25) : rand(0, 8),
    ]);
    $pid = (int) $pdo->lastInsertId();
    $insImage->execute([$pid, $path, $name, 0]);

    // Segunda imagem: próxima da sequência se existir (ângulo extra)
    $nextIdx = $idx + 1;
    $nextPath = dirname(__DIR__) . '/public/uploads/products/bali/bali-' . str_pad((string) $nextIdx, 3, '0', STR_PAD_LEFT) . '.jpg';
    if ($lookNum % 3 === 0 && file_exists($nextPath) && $nextIdx > 2) {
        $insImage->execute([$pid, '/uploads/products/bali/bali-' . str_pad((string) $nextIdx, 3, '0', STR_PAD_LEFT) . '.jpg', $name, 1]);
    }

    $color = $colors[($lookNum - 1) % count($colors)];
    foreach ($sizes as $size) {
        $stock = $size === 'M' ? rand(4, 12) : rand(1, 8);
        $sku = 'BALI-' . str_pad((string) $lookNum, 3, '0', STR_PAD_LEFT) . "-{$size}";
        $insVariant->execute([$pid, $size, $color[0], $color[1], $sku, $stock]);
    }

    $created++;
    if ($created % 10 === 0) {
        echo "criados $created...\n";
    }
}

// Hero + settings da coleção
$set = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
$set->execute(['hero_image', $cover]);
$set->execute(['hero_title', 'Coleção Bali']);
$set->execute(['hero_subtitle', 'Verão 27 — leveza, texturas artesanais e tons neutros inspirados em Bali.']);
$set->execute(['tagline', 'Coleção Bali · Verão 27']);

// Instagram feed com fotos Bali
$ig = [];
foreach ([3, 5, 15, 25, 40, 55, 70, 85] as $n) {
    $f = '/uploads/products/bali/bali-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT) . '.jpg';
    if (file_exists(dirname(__DIR__) . '/public' . $f)) {
        $ig[] = $f;
    }
}
$set->execute(['instagram_feed', json_encode(array_slice($ig, 0, 6), JSON_UNESCAPED_SLASHES)]);

// Atualiza produtos demo antigos para também usarem fotos Bali (mantém IDs)
$legacy = $pdo->query("SELECT id, slug FROM products WHERE slug NOT LIKE 'bali-look-%' ORDER BY id")->fetchAll();
$baliFiles = glob(dirname(__DIR__) . '/public/uploads/products/bali/bali-*.jpg');
sort($baliFiles);
$baliFiles = array_values(array_filter($baliFiles, function ($f) {
    $base = basename($f);
    // pula capas 001 e 002
    return !preg_match('/bali-00[12]\.jpg$/', $base);
}));

foreach ($legacy as $i => $p) {
    if (!isset($baliFiles[$i])) {
        break;
    }
    $rel = '/uploads/products/bali/' . basename($baliFiles[$i]);
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$p['id']]);
    $insImage->execute([(int) $p['id'], $rel, $p['slug'], 0]);
    echo "atualizado produto demo #{$p['id']} -> $rel\n";
}

echo "\nPronto: $created produtos da Coleção Bali cadastrados.\n";
echo "Hero e Instagram atualizados.\n";
echo "Abra /categoria/colecao-bali\n";

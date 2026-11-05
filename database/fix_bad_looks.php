<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$badFiles = [
    'bali-001.jpg',
    'bali-002.jpg',
    'bali-012.jpg',
    'bali-069.jpg',
];

$dir = dirname(__DIR__) . '/public/uploads/products/bali';
foreach (glob($dir . '/bali-*.jpg') as $path) {
    $info = @getimagesize($path);
    if (!$info) {
        continue;
    }
    [$w, $h] = $info;
    $aspect = $w / max(1, $h);
    if ($aspect < 0.5 || $aspect > 1.1) {
        $badFiles[] = basename($path);
    }
}
$badFiles = array_values(array_unique($badFiles));

foreach ($badFiles as $file) {
    $stmt = $pdo->prepare(
        "UPDATE products SET is_active = 0
         WHERE id IN (SELECT product_id FROM product_images WHERE path LIKE ?)"
    );
    $stmt->execute(['%' . $file]);
    echo "off $file ({$stmt->rowCount()})\n";
}

// Libera TODOS os slugs bali temporariamente
$all = $pdo->query("SELECT id FROM products WHERE slug LIKE 'bali-look-%' OR slug LIKE 'bali-tmp-%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($all as $id) {
    $pdo->prepare('UPDATE products SET slug = ? WHERE id = ?')->execute(['bali-tmp-' . $id, $id]);
}

$active = $pdo->query(
    "SELECT p.id FROM products p
     WHERE p.is_active = 1 AND (p.slug LIKE 'bali-tmp-%' OR p.slug LIKE 'bali-look-%')
     ORDER BY p.id"
)->fetchAll(PDO::FETCH_COLUMN);

$n = 1;
foreach ($active as $id) {
    $name = 'Bali Look ' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    $slug = 'bali-look-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    $pdo->prepare('UPDATE products SET name = ?, slug = ?, description = ? WHERE id = ?')->execute([
        $name,
        $slug,
        'Peça da Coleção Bali — Verão 27. Leveza, texturas artesanais e tons neutros. Foto oficial da campanha Mandalla.',
        $id,
    ]);
    $pdo->prepare('UPDATE product_images SET alt = ? WHERE product_id = ?')->execute([$name, $id]);
    $n++;
}

$pdo->exec("UPDATE products SET is_featured=0, is_new=0, is_sale=0, is_bestseller=0, compare_at_price=NULL WHERE slug LIKE 'bali-look-%' OR slug LIKE 'bali-tmp-%'");

foreach ($active as $i => $id) {
    $sale = in_array($i, [2, 6], true);
    $pdo->prepare('UPDATE products SET is_featured=?, is_new=?, is_bestseller=?, is_sale=?, compare_at_price=? WHERE id=?')
        ->execute([
            $i < 8 ? 1 : 0,
            $i < 10 ? 1 : 0,
            in_array($i, [1, 3, 5, 7], true) ? 1 : 0,
            $sale ? 1 : 0,
            $sale ? null : null,
            $id,
        ]);
}
$pdo->exec("UPDATE products SET compare_at_price = ROUND(price * 1.18, 2) WHERE is_sale = 1 AND slug LIKE 'bali-look-%'");

echo 'ativos=' . count($active) . "\n";

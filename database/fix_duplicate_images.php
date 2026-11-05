<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$root = dirname(__DIR__) . '/public';

// Mapa: path => [product_ids]
$rows = $pdo->query(
    "SELECT p.id, p.name, p.slug, i.id AS image_id, i.path, i.sort_order
     FROM products p
     JOIN product_images i ON i.product_id = p.id
     WHERE p.is_active = 1
     ORDER BY i.path, i.sort_order, p.id"
)->fetchAll();

$byPath = [];
foreach ($rows as $r) {
    $byPath[$r['path']][] = $r;
}

echo "=== Paths usados por mais de 1 produto (sort_order=0) ===\n";
$primaries = $pdo->query(
    "SELECT i.path, GROUP_CONCAT(p.id) AS ids, COUNT(*) AS n
     FROM product_images i
     JOIN products p ON p.id = i.product_id AND p.is_active = 1
     WHERE i.sort_order = 0
     GROUP BY i.path
     HAVING n > 1"
)->fetchAll();

foreach ($primaries as $p) {
    echo $p['n'] . 'x ' . $p['path'] . ' -> products ' . $p['ids'] . PHP_EOL;
}
echo 'duplicate_primary_groups=' . count($primaries) . PHP_EOL;

// Lista todos arquivos bali disponíveis
$baliDir = $root . '/uploads/products/bali';
$files = glob($baliDir . '/bali-*.jpg') ?: [];
sort($files);
$available = [];
foreach ($files as $f) {
    $base = basename($f);
    // pula capas
    if (preg_match('/bali-00[12]\.jpg$/', $base)) {
        continue;
    }
    $available[] = '/uploads/products/bali/' . $base;
}

// Paths já usados como principal
$used = [];
foreach ($pdo->query('SELECT path FROM product_images WHERE sort_order = 0') as $r) {
    $used[$r['path']] = true;
}

$products = $pdo->query(
    "SELECT p.id, p.slug, p.name,
            (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS path
     FROM products p
     WHERE p.is_active = 1 AND p.slug LIKE 'bali-look-%'
     ORDER BY p.id"
)->fetchAll();

$updDel = $pdo->prepare('DELETE FROM product_images WHERE product_id = ?');
$updIns = $pdo->prepare('INSERT INTO product_images (product_id, path, alt, sort_order) VALUES (?, ?, ?, 0)');

// Reatribui: 1 produto = 1 foto única, em ordem
$pool = $available;
$assigned = [];
$i = 0;
foreach ($products as $p) {
    if (!isset($pool[$i])) {
        echo "SEM FOTO EXTRA para #{$p['id']} {$p['slug']}\n";
        break;
    }
    $path = $pool[$i];
    $i++;
    $updDel->execute([(int) $p['id']]);
    $updIns->execute([(int) $p['id'], $path, $p['name']]);
    $assigned[$path] = (int) $p['id'];
    echo "OK #{$p['id']} {$p['name']} -> {$path}\n";
}

// Remove imagens secundárias duplicadas que sobraram (já deletamos todas e recolocamos só 1)

echo "\n=== Conferência ===\n";
$dup = $pdo->query(
    "SELECT path, COUNT(*) n FROM product_images WHERE sort_order = 0 GROUP BY path HAVING n > 1"
)->fetchAll();
echo 'ainda_duplicadas=' . count($dup) . PHP_EOL;
echo 'produtos=' . count($products) . ' fotos_pool=' . count($available) . ' atribuidas=' . count($assigned) . PHP_EOL;

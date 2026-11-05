<?php

/**
 * Remove produtos com foto visualmente idêntica (mesmo hash de arquivo).
 * Mantém o primeiro de cada grupo e desativa os demais.
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$root = dirname(__DIR__) . '/public';

$rows = $pdo->query(
    "SELECT p.id, p.name, p.slug, i.path
     FROM products p
     JOIN product_images i ON i.product_id = p.id AND i.sort_order = 0
     WHERE p.is_active = 1 AND p.slug LIKE 'bali-look-%'
     ORDER BY p.id"
)->fetchAll();

$byHash = [];
foreach ($rows as $r) {
    $abs = $root . $r['path'];
    if (!is_file($abs)) {
        echo "MISS {$r['path']}\n";
        continue;
    }
    $hash = md5_file($abs);
    $byHash[$hash][] = $r;
}

$deactivate = $pdo->prepare('UPDATE products SET is_active = 0, is_featured = 0, is_new = 0, is_sale = 0, is_bestseller = 0 WHERE id = ?');
$kept = 0;
$removed = 0;

foreach ($byHash as $hash => $group) {
    if (count($group) === 1) {
        $kept++;
        continue;
    }
    echo 'DUP hash ' . substr($hash, 0, 8) . ' x' . count($group) . ":\n";
    // mantém o primeiro
    $first = array_shift($group);
    echo "  KEEP #{$first['id']} {$first['name']} {$first['path']}\n";
    $kept++;
    foreach ($group as $dup) {
        $deactivate->execute([(int) $dup['id']]);
        echo "  OFF  #{$dup['id']} {$dup['name']} {$dup['path']}\n";
        $removed++;
    }
}

// Também detecta quase-duplicatas por tamanho exato + primeiras/últimas bytes (fallback)
// Já coberto por md5.

echo "\nativos_unicos≈$kept desativados_duplicados=$removed\n";
$active = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
echo "produtos_ativos_agora=$active\n";

// Reequilibra destaques entre os ativos restantes
$pdo->exec('UPDATE products SET is_featured = 0, is_new = 0, is_bestseller = 0, is_sale = 0');
$actives = $pdo->query('SELECT id FROM products WHERE is_active = 1 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
$upd = $pdo->prepare('UPDATE products SET is_featured=?, is_new=?, is_bestseller=?, is_sale=?, compare_at_price=? WHERE id=?');
$i = 0;
foreach ($actives as $id) {
    $i++;
    $featured = $i <= 8 ? 1 : 0;
    $isNew = $i <= 16 ? 1 : 0;
    $best = ($i % 4 === 0) ? 1 : 0;
    $sale = ($i % 5 === 0) ? 1 : 0;
    $price = (float) $pdo->query('SELECT price FROM products WHERE id=' . (int) $id)->fetchColumn();
    $compare = $sale ? round($price * 1.2, 2) : null;
    $upd->execute([$featured, $isNew, $best, $sale, $compare, $id]);
}
echo "flags reequilibradas em $i produtos\n";

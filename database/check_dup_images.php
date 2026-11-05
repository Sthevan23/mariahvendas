<?php

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$rows = $pdo->query(
    "SELECT p.id, p.slug, pi.path
     FROM products p
     JOIN product_images pi ON pi.product_id = p.id AND pi.sort_order = 0
     WHERE p.is_active = 1
     ORDER BY p.id"
)->fetchAll(PDO::FETCH_ASSOC);

$hashes = [];
$dups = [];

foreach ($rows as $r) {
    $f = __DIR__ . '/../public/' . $r['path'];
    if (!is_file($f)) {
        echo "MISSING {$r['path']}\n";
        continue;
    }
    $h = md5_file($f);
    if (isset($hashes[$h])) {
        $dups[] = [$hashes[$h], $r];
    } else {
        $hashes[$h] = $r;
    }
}

echo 'produtos=' . count($rows) . ' unicos=' . count($hashes) . ' dups=' . count($dups) . PHP_EOL;
foreach ($dups as $d) {
    echo "DUP: {$d[0]['slug']} == {$d[1]['slug']} ({$d[0]['path']})\n";
}

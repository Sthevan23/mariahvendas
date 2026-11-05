<?php

/**
 * Recria produtos Bali com 1 foto única por página (CLI).
 */
require dirname(__DIR__) . '/bootstrap.php';
require_once __DIR__ . '/seed_bali_catalog.php';

use App\Core\Database;

$pdo = Database::connection();
$result = mandalla_seed_bali_catalog($pdo, dirname(__DIR__));

echo "removidos {$result['removed']} produtos antigos\n";
echo "criados={$result['created']} dups={$result['dups']}\n";
foreach ($pdo->query('SELECT c.slug, COUNT(p.id) n FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.is_active=1 GROUP BY c.id ORDER BY n DESC') as $r) {
    echo "  {$r['slug']}: {$r['n']}\n";
}

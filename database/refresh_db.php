<?php

/**
 * Recria o SQLite local com a Coleção Bali (para versionar no Git).
 */
$db = __DIR__ . '/mandalla.db';
foreach ([$db, $db . '-journal', $db . '-wal', $db . '-shm'] as $f) {
    if (is_file($f)) {
        unlink($f);
    }
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$n = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE slug LIKE 'bali-look-%'")->fetchColumn();
$ver = (string) ($pdo->query("SELECT value FROM settings WHERE key = 'catalog_version'")->fetchColumn() ?: '');
echo "ok bali={$n} version={$ver} file={$db} size=" . filesize($db) . "\n";

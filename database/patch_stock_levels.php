<?php

declare(strict_types=1);

/**
 * Normaliza estoque do catálogo (evita alertas falsos no dashboard).
 * CLI: php database/patch_stock_levels.php
 */
function mariah_patch_stock_levels(int $min = 12, int $max = 28): string
{
    $pdo = App\Core\Database::connection();
    $rows = $pdo->query('SELECT id, stock FROM product_variants')->fetchAll(PDO::FETCH_ASSOC);
    $updated = 0;
    $upd = $pdo->prepare('UPDATE product_variants SET stock = ? WHERE id = ?');

    foreach ($rows as $row) {
        if ((int) $row['stock'] >= $min) {
            continue;
        }
        $upd->execute([random_int($min, $max), (int) $row['id']]);
        $updated++;
    }

    $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)')
        ->execute(['low_stock_threshold', '3']);

    return "Variantes atualizadas: {$updated}. Alerta de estoque: <= 3 un por produto.";
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require dirname(__DIR__) . '/bootstrap.php';
    echo mariah_patch_stock_levels() . "\n";
}

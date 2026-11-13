<?php

declare(strict_types=1);

/**
 * Remove do banco as fotos enviadas pelo admin (/uploads/products/p_*) cujo arquivo sumiu do servidor.
 * Não toca nas fotos do catálogo original nem nas fotos novas (/media/...).
 *
 * Ver o que seria removido (não altera nada):  php database/remove_missing_photos.php
 * Remover de verdade:                           php database/remove_missing_photos.php --apply
 */
require dirname(__DIR__) . '/bootstrap.php';

$apply = in_array('--apply', $argv ?? [], true);
$public = dirname(__DIR__) . '/public';
$pdo = App\Core\Database::connection();

$rows = $pdo->query(
    "SELECT i.id, i.path, p.name FROM product_images i
     JOIN products p ON p.id = i.product_id
     WHERE i.path LIKE '/uploads/products/p\\_%'
     ORDER BY p.name, i.id"
)->fetchAll();

$missing = array_values(array_filter($rows, static fn(array $r): bool => !is_file($public . $r['path'])));

printf("Fotos enviadas pelo admin no banco: %d | com arquivo sumido: %d\n", count($rows), count($missing));
foreach ($missing as $r) {
    printf("  - %s  (%s)\n", $r['name'], $r['path']);
}

if (!$missing) {
    exit("Nada a remover.\n");
}
if (!$apply) {
    exit("\nNada foi alterado. Para remover essas linhas do banco, rode com --apply\n");
}

$del = $pdo->prepare('DELETE FROM product_images WHERE id = ?');
foreach ($missing as $r) {
    $del->execute([$r['id']]);
}
printf("\nRemovidas %d fotos quebradas do banco.\n", count($missing));

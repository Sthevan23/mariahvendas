<?php

declare(strict_types=1);

/**
 * Padroniza tamanho (p -> P) e cor (azul marinho -> Azul Marinho) das variações já cadastradas.
 * Não apaga nada. Se a versão padronizada já existir no mesmo produto, só avisa (conflito).
 * Variações com tamanho ou cor "0"/vazio são apenas listadas, para a cliente corrigir no admin.
 *
 * Ver o que mudaria (não altera nada):  php database/normalize_variants.php
 * Aplicar de verdade:                    php database/normalize_variants.php --apply
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Models\Product;

$apply = in_array('--apply', $argv ?? [], true);
$pdo = App\Core\Database::connection();

$rows = $pdo->query(
    'SELECT v.id, v.product_id, v.size, v.color, p.name FROM product_variants v
     JOIN products p ON p.id = v.product_id ORDER BY v.product_id, v.id'
)->fetchAll();

$taken = [];
foreach ($rows as $r) {
    $taken[$r['product_id'] . '|' . mb_strtolower($r['size']) . '|' . mb_strtolower($r['color'])] = $r['id'];
}

$changes = [];
$conflicts = [];
$invalid = [];
foreach ($rows as $r) {
    $size = Product::normalizeSize($r['size']);
    $color = Product::normalizeColor($r['color']);

    if (in_array($size, ['', '0'], true) || in_array($color, ['', '0'], true)) {
        $invalid[] = $r;
        continue;
    }
    if ($size === $r['size'] && $color === $r['color']) {
        continue;
    }
    $key = $r['product_id'] . '|' . mb_strtolower($size) . '|' . mb_strtolower($color);
    if (isset($taken[$key]) && $taken[$key] !== $r['id']) {
        $conflicts[] = $r + ['new_size' => $size, 'new_color' => $color];
        continue;
    }
    $changes[] = $r + ['new_size' => $size, 'new_color' => $color];
}

printf("Variações: %d | a padronizar: %d | conflitos: %d | inválidas (0/vazio): %d\n\n", count($rows), count($changes), count($conflicts), count($invalid));

foreach ($changes as $c) {
    printf("  ajustar  %s: %s/%s -> %s/%s\n", $c['name'], $c['size'], $c['color'], $c['new_size'], $c['new_color']);
}
foreach ($conflicts as $c) {
    printf("  CONFLITO %s: %s/%s já existe como %s/%s (não alterado)\n", $c['name'], $c['size'], $c['color'], $c['new_size'], $c['new_color']);
}
foreach ($invalid as $c) {
    printf("  INVÁLIDA %s: tamanho \"%s\" cor \"%s\" (corrigir no admin)\n", $c['name'], $c['size'], $c['color']);
}

if (!$changes) {
    exit("\nNada a padronizar.\n");
}
if (!$apply) {
    exit("\nNada foi alterado. Para aplicar, rode com --apply\n");
}

$upd = $pdo->prepare('UPDATE product_variants SET size = ?, color = ? WHERE id = ?');
foreach ($changes as $c) {
    $upd->execute([$c['new_size'], $c['new_color'], $c['id']]);
}
printf("\nPadronizadas %d variações.\n", count($changes));

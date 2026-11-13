<?php

declare(strict_types=1);

/**
 * Restaura os códigos dos vestidos (ex.: 2475, A110) que o cliente tinha digitado no campo de cor
 * e que foram sobrescritos pelo fill_colors.php. Os códigos voltam no campo "Código" de cada variação.
 * Só preenche variação SEM código; nunca sobrescreve um código que já exista.
 *
 * Ver o que faria (não altera nada):  php database/restore_codes.php
 * Aplicar de verdade:                  php database/restore_codes.php --apply
 */
require dirname(__DIR__) . '/bootstrap.php';

$apply = in_array('--apply', $argv ?? [], true);
$pdo = App\Core\Database::connection();

// Código de cada vestido, recuperado da listagem que o fill_colors.php mostrou antes de aplicar.
$codes = [
    'tomara-que-caia' => '247',
    'vestido-de-um-ombro-so' => '2464',
    'vestido-de-um-ombro-so-2' => '100',
    'vestido-evase' => '243',
    'vestido-evaze' => '222',
    'vestido-evaze-2' => '245',
    'vestido-evaze-3' => '2387',
    'vestido-evaze-4' => '2434',
    'vestido-ombro-a-ombro' => '249',
    'vestido-princesa' => '2433L',
    'vestido-rodado' => '244',
    'vestido-sereia' => '231',
    'vestido-sereia-2' => 'A110',
    'vestido-sereia-3' => '226',
    'vestido-sereia-4' => '2441',
    'vestido-sereia-5' => '2475',
    'vestido-sereia-6' => '197',
    'vestido-tomara-que-caia' => '2440',
];

$find = $pdo->prepare('SELECT id, name FROM products WHERE slug = ?');
$vars = $pdo->prepare("SELECT id, size, color FROM product_variants WHERE product_id = ? AND (sku IS NULL OR sku = '')");
$upd = $pdo->prepare('UPDATE product_variants SET sku = ? WHERE id = ?');
$total = 0;

foreach ($codes as $slug => $code) {
    $find->execute([$slug]);
    $p = $find->fetch();
    if (!$p) {
        echo "  NÃO ACHEI o produto '$slug' (pulei)\n";
        continue;
    }
    $vars->execute([$p['id']]);
    $rows = $vars->fetchAll();
    printf("  %s (%s): código %s em %d variações\n", $p['name'], $slug, $code, count($rows));
    foreach ($rows as $r) {
        if ($apply) {
            $upd->execute([$code, $r['id']]);
        }
        $total++;
    }
}

printf("\nVariações %s: %d\n", $apply ? 'restauradas' : 'a restaurar', $total);
if (!$apply) {
    echo "Nada foi alterado. Para aplicar: php database/restore_codes.php --apply\n";
}

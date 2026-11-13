<?php

declare(strict_types=1);

/**
 * Preenche o código de cor (bolinha) das variações que estão sem cor, brancas ou cinza por engano,
 * usando o nome da cor (Vinho, Azul Marinho, Verde Esmeralda...). Não mexe em tamanho, cor nem estoque.
 *
 * Ver o que mudaria (não altera nada):  php database/fill_colors.php
 * Aplicar de verdade:                    php database/fill_colors.php --apply
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Helpers;

$apply = in_array('--apply', $argv ?? [], true);
$pdo = App\Core\Database::connection();

$rows = $pdo->query(
    'SELECT v.id, v.color, v.color_hex, p.name FROM product_variants v
     JOIN products p ON p.id = v.product_id ORDER BY p.name, v.color'
)->fetchAll();

$upd = $pdo->prepare('UPDATE product_variants SET color_hex = ? WHERE id = ?');
$changed = 0;
$unknown = [];
foreach ($rows as $r) {
    $new = Helpers::swatchHex($r['color'], $r['color_hex']);
    if (strlen($new) === 4) {
        $new = '#' . $new[1] . $new[1] . $new[2] . $new[2] . $new[3] . $new[3];
    }
    if (Helpers::colorHex($r['color'], '') === '') {
        $unknown[$r['color']] = true;
    }
    if (strtolower((string) $r['color_hex']) === strtolower($new)) {
        continue;
    }
    $changed++;
    printf("  %s | %s: %s -> %s\n", $r['name'], $r['color'], $r['color_hex'] ?: '(vazio)', $new);
    if ($apply) {
        $upd->execute([$new, $r['id']]);
    }
}

printf("\nVariações: %d | %s: %d\n", count($rows), $apply ? 'atualizadas' : 'a atualizar', $changed);
if ($unknown) {
    echo "Cores sem equivalente conhecido (ficam cinza claro; a cliente ajusta na bolinha do admin): " . implode(', ', array_keys($unknown)) . "\n";
}
if (!$apply) {
    echo "Nada foi alterado. Para aplicar: php database/fill_colors.php --apply\n";
}

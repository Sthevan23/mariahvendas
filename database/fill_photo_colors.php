<?php

declare(strict_types=1);

/**
 * Marca a cor de cada foto olhando a cor do vestido na imagem, para a bolinha trocar a foto na loja.
 * Só mexe em foto SEM cor e em produto com 2 ou mais cores. Dúvida (cores muito parecidas) fica de fora.
 *
 * Ver o que marcaria (não altera nada):  php database/fill_photo_colors.php
 * Aplicar de verdade:                     php database/fill_photo_colors.php --apply
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Helpers;
use App\Models\Product;
use App\Services\MediaStore;
use App\Services\PhotoColor;

$apply = in_array('--apply', $argv ?? [], true);
$pdo = App\Core\Database::connection();

if (!function_exists('imagecreatefromstring')) {
    exit("Este servidor não tem a extensão GD do PHP, então não dá para ler as fotos.\n");
}
if (!Product::hasImageColor()) {
    exit("Não consegui criar a coluna de cor das fotos no banco (sem permissão). Avise o desenvolvedor.\n");
}

$products = $pdo->query('SELECT id, name FROM products ORDER BY name')->fetchAll();
$done = 0;
$skipped = 0;

foreach ($products as $p) {
    $variants = Product::variants((int) $p['id']);
    $colors = [];
    foreach ($variants as $v) {
        $colors[$v['color']] = Helpers::swatchHex($v['color'], $v['color_hex']);
    }
    if (count($colors) < 2) {
        continue;
    }
    echo "\n{$p['name']}  (cores: " . implode(', ', array_keys($colors)) . ")\n";

    foreach (Product::images((int) $p['id']) as $img) {
        if (!empty($img['color'])) {
            echo "  foto {$img['id']}: já marcada como {$img['color']}\n";
            continue;
        }
        $path = (string) $img['path'];
        if (MediaStore::isMediaPath($path)) {
            $file = MediaStore::dir() . '/' . MediaStore::nameFromPath($path);
        } elseif (str_starts_with($path, '/')) {
            $file = dirname(__DIR__) . '/public' . $path;
        } else {
            echo "  foto {$img['id']}: link externo, pulei\n";
            $skipped++;
            continue;
        }
        if (!is_file($file)) {
            echo "  foto {$img['id']}: arquivo não encontrado, pulei\n";
            $skipped++;
            continue;
        }
        $rgb = PhotoColor::dominantRgb($file);
        $match = $rgb ? PhotoColor::nearest($rgb, $colors) : null;
        if (!$match) {
            echo "  foto {$img['id']}: não consegui ler a cor, pulei\n";
            $skipped++;
            continue;
        }
        [$name, $dist, $margin] = $match;
        if ($margin < 3 || $dist > 60) {
            printf("  foto %d: ficou na dúvida (parece %s), deixei sem cor — marque no admin\n", $img['id'], $name);
            $skipped++;
            continue;
        }
        printf("  foto %d: %s%s\n", $img['id'], $name, $margin < 10 ? '  (confira esta)' : '');
        if ($apply) {
            Product::setImageColor((int) $p['id'], (int) $img['id'], $name);
        }
        $done++;
    }
}

printf("\nFotos %s: %d | puladas: %d\n", $apply ? 'marcadas' : 'a marcar', $done, $skipped);
if (!$apply) {
    echo "Nada foi alterado. Para aplicar: php database/fill_photo_colors.php --apply\n";
}

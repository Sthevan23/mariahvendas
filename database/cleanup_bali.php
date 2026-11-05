<?php

/**
 * Limpa flags, desativa demos antigos e remove looks com foto inválida (capa/editorial/objeto).
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

// 1) Desativa produtos demo antigos (não-Bali)
$pdo->exec("UPDATE products SET is_active = 0, is_featured = 0, is_new = 0, is_sale = 0, is_bestseller = 0
            WHERE slug NOT LIKE 'bali-look-%'");
echo "Demos antigos desativados\n";

// 2) Reseta flags Bali
$pdo->exec("UPDATE products SET is_featured = 0, is_new = 0, is_sale = 0, is_bestseller = 0
            WHERE slug LIKE 'bali-look-%'");

// 3) Detecta imagens ruins via análise simples (Pillow via python)
$py = <<<'PY'
from PIL import Image
import json, os, statistics
root = r"c:\Users\Administrador\OneDrive\Desktop\mandalla\public\uploads\products\bali"
bad = []
for name in sorted(os.listdir(root)):
    if not name.endswith('.jpg') or name.startswith('_'):
        continue
    path = os.path.join(root, name)
    im = Image.open(path).convert('RGB')
    w, h = im.size
    aspect = w / h if h else 1
    # amostra
    small = im.resize((64, 64))
    pixels = list(small.getdata())
    # variância de luminância
    lum = [0.2126*r + 0.7152*g + 0.0722*b for r,g,b in pixels]
    var = statistics.pstdev(lum)
    mean = sum(lum)/len(lum)
    # regras: landscape demais, quase branco flat, ou muito quadrado editorial
    reasons = []
    if aspect > 1.05:
        reasons.append('landscape')
    if var < 18 and mean > 180:
        reasons.append('flat-bright')
    if var < 12:
        reasons.append('low-contrast')
    # capa tipográfica costuma ter área central muito branca + texto
    if name in ('bali-001.jpg','bali-002.jpg'):
        reasons.append('cover')
    if reasons:
        bad.append({'file': name, 'reasons': reasons, 'aspect': round(aspect,2), 'var': round(var,1), 'mean': round(mean,1)})
print(json.dumps(bad, ensure_ascii=False))
PY;

$tmp = sys_get_temp_dir() . '/bali_analyze.py';
file_put_contents($tmp, $py);
$json = shell_exec('python ' . escapeshellarg($tmp) . ' 2>&1');
$bad = json_decode((string) $json, true) ?: [];
echo "Imagens suspeitas: " . count($bad) . "\n";

$badFiles = [];
foreach ($bad as $b) {
    $badFiles[$b['file']] = $b['reasons'];
    echo " - {$b['file']}: " . implode(',', $b['reasons']) . "\n";
}

// Desativa produtos cuja imagem principal está na lista ruim
$rows = $pdo->query("SELECT p.id, p.slug, i.path FROM products p
    JOIN product_images i ON i.product_id = p.id AND i.sort_order = 0
    WHERE p.slug LIKE 'bali-look-%'")->fetchAll();

$disabled = 0;
foreach ($rows as $row) {
    $file = basename((string) $row['path']);
    if (isset($badFiles[$file])) {
        $pdo->prepare('UPDATE products SET is_active = 0 WHERE id = ?')->execute([$row['id']]);
        $disabled++;
    }
}
echo "Produtos desativados por foto inválida: $disabled\n";

// 4) Flags equilibradas nos produtos Bali ativos
$active = $pdo->query("SELECT id FROM products WHERE is_active = 1 AND slug LIKE 'bali-look-%' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
$total = count($active);
echo "Bali ativos: $total\n";

foreach ($active as $i => $id) {
    $featured = $i < 8 ? 1 : 0;
    $isNew = $i < 12 ? 1 : 0;
    $best = in_array($i, [1, 3, 5, 7, 10, 15], true) ? 1 : 0;
    $sale = in_array($i, [2, 6, 11, 18], true) ? 1 : 0;
    $compare = $sale ? null : null;
    $pdo->prepare('UPDATE products SET is_featured = ?, is_new = ?, is_bestseller = ?, is_sale = ? WHERE id = ?')
        ->execute([$featured, $isNew, $best, $sale, $id]);
    if ($sale) {
        $pdo->prepare('UPDATE products SET compare_at_price = ROUND(price * 1.2, 2) WHERE id = ? AND (compare_at_price IS NULL OR compare_at_price = 0)')
            ->execute([$id]);
    } else {
        $pdo->prepare('UPDATE products SET compare_at_price = NULL WHERE id = ?')->execute([$id]);
    }
}

// 5) Remove segunda imagem errada (era o look seguinte)
$pdo->exec('DELETE FROM product_images WHERE sort_order > 0 AND product_id IN (SELECT id FROM products WHERE slug LIKE \'bali-look-%\')');
echo "Galerias extras removidas\n";

// 6) Nomes limpos: Bali Look 01, 02...
$n = 1;
foreach ($active as $id) {
    $name = 'Bali Look ' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    $slug = 'bali-look-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    // evita conflito de slug único
    $pdo->prepare('UPDATE products SET name = ?, slug = ? WHERE id = ?')->execute([$name, $slug . '-tmp', $id]);
    $n++;
}
$n = 1;
foreach ($active as $id) {
    $name = 'Bali Look ' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    $slug = 'bali-look-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    $desc = "Peça da Coleção Bali — Verão 27. Leveza, texturas artesanais e tons neutros. Foto oficial da campanha Mandalla.";
    $pdo->prepare('UPDATE products SET name = ?, slug = ?, description = ? WHERE id = ?')->execute([$name, $slug, $desc, $id]);
    $pdo->prepare('UPDATE product_images SET alt = ? WHERE product_id = ?')->execute([$name, $id]);
    $n++;
}

echo "Flags e nomes atualizados.\n";
echo "OK\n";

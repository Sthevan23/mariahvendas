<?php

/**
 * Corrige catálogo pós-import Bali:
 * - distribui produtos nas categorias (vestidos, blusas, etc.)
 * - renomeia looks
 * - ajusta flags da home (destaque / novo / promo / bestseller)
 * - garante estoque
 * - remove produtos demo inativos órfãos
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$cats = [];
foreach ($pdo->query('SELECT id, slug FROM categories') as $c) {
    $cats[$c['slug']] = (int) $c['id'];
}

$typeCycle = ['vestidos', 'conjuntos', 'blusas', 'saias', 'calcas', 'vestidos', 'conjuntos', 'blusas'];
$namesByType = [
    'vestidos' => ['Vestido Bali Midi', 'Vestido Strapless Bali', 'Vestido Curto Verão 27', 'Vestido Longo Brisa', 'Vestido Envelope Bali', 'Vestido Neutro Areia'],
    'conjuntos' => ['Conjunto Bali Off-White', 'Conjunto Peplum Dourado', 'Conjunto Textura Artesanal', 'Conjunto Resort Wear', 'Conjunto Botões Ouro', 'Conjunto Linho Bali'],
    'blusas' => ['Blusa Ombro a Ombro Bali', 'Top Cropped Linho Bali', 'Blusa Cetim Areia Bali', 'Top Alça Fina Bali', 'Camisa Leve Bali', 'Blusa Bordada Verão'],
    'saias' => ['Saia Midi Bali Natural', 'Saia Flores Bali', 'Saia Plissada Verão', 'Saia Linho Creme', 'Saia Curta Tropical', 'Saia Envelope Bali'],
    'calcas' => ['Calça Wide Bali Creme', 'Calça Alfaiataria Neutra', 'Calça Linho Solta', 'Pantalona Bali', 'Calça Reta Areia', 'Shorts Linho Tropical'],
];

$products = $pdo->query("SELECT id, slug FROM products WHERE slug LIKE 'bali-look-%' ORDER BY id ASC")->fetchAll();
$upd = $pdo->prepare(
    'UPDATE products SET category_id=?, name=?, description=?, is_active=1, is_featured=?, is_new=?, is_sale=?, is_bestseller=?, compare_at_price=?, sold_count=?, price=? WHERE id=?'
);

$i = 0;
foreach ($products as $p) {
    $i++;
    $type = $typeCycle[($i - 1) % count($typeCycle)];
    $catId = $cats[$type] ?? $cats['colecao-bali'];
    $nameList = $namesByType[$type];
    $name = $nameList[($i - 1) % count($nameList)] . ' ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);

    $price = [189.9, 219.9, 249.9, 279.9, 299.9, 329.9, 359.9][($i - 1) % 7];
    $featured = $i <= 8 ? 1 : 0;
    $isNew = $i <= 16 ? 1 : (($i % 3 === 0) ? 1 : 0);
    $isSale = ($i % 5 === 0) ? 1 : 0;
    $best = ($i % 4 === 0) ? 1 : 0;
    $compare = $isSale ? round($price * 1.2, 2) : null;
    $sold = $best ? rand(12, 40) : rand(0, 9);

    $desc = "Peça da Coleção Bali — Verão 27 da Mandalla. "
        . "Leveza, texturas artesanais e tons neutros. Look {$i} da campanha.";

    $upd->execute([$catId, $name, $desc, $featured, $isNew, $isSale, $best, $compare, $sold, $price, $p['id']]);
}

// Garante estoque mínimo em M de cada produto Bali
$pdo->exec("UPDATE product_variants SET stock = 5 WHERE stock <= 0 AND size = 'M' AND product_id IN (SELECT id FROM products WHERE slug LIKE 'bali-look-%')");
$pdo->exec("UPDATE product_variants SET stock = 3 WHERE stock <= 0 AND product_id IN (SELECT id FROM products WHERE slug LIKE 'bali-look-%')");

// Remove produtos demo antigos inativos (não Bali)
$pdo->exec("DELETE FROM products WHERE is_active = 0 AND slug NOT LIKE 'bali-look-%'");
// Soft-hide any remaining non-bali leftovers from old seed if still around
$pdo->exec("UPDATE products SET is_active = 0 WHERE slug NOT LIKE 'bali-look-%'");

// Atualiza imagens de categorias com fotos Bali
$catImages = [
    'vestidos' => '/uploads/products/bali/bali-003.jpg',
    'blusas' => '/uploads/products/bali/bali-015.jpg',
    'calcas' => '/uploads/products/bali/bali-025.jpg',
    'conjuntos' => '/uploads/products/bali/bali-005.jpg',
    'saias' => '/uploads/products/bali/bali-040.jpg',
    'jeans' => '/uploads/products/bali/bali-055.jpg',
    'acessorios' => '/uploads/products/bali/bali-070.jpg',
    'promocoes' => '/uploads/products/bali/bali-007.jpg',
    'novidades' => '/uploads/products/bali/bali-004.jpg',
    'colecao-bali' => '/uploads/products/bali/bali-001.jpg',
];
$uc = $pdo->prepare('UPDATE categories SET image = ? WHERE slug = ?');
foreach ($catImages as $slug => $img) {
    if (is_file(dirname(__DIR__) . '/public' . $img)) {
        $uc->execute([$img, $slug]);
    }
}

echo "Produtos Bali redistribuídos: $i\n";
foreach ($pdo->query('SELECT c.slug, COUNT(p.id) n FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.is_active=1 GROUP BY c.id ORDER BY n DESC') as $r) {
    echo "  {$r['slug']}: {$r['n']}\n";
}

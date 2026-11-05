<?php

/**
 * Catálogo Mariah — looks com fotos geradas em /uploads/moda/colecao/
 *
 * @return array{created:int, removed:int, dups:int}
 */
if (!function_exists('mariah_seed_catalog')) {
function mariah_seed_catalog(PDO $pdo, string $root): array
{
    $dir = $root . '/public/uploads/moda/colecao';
    if (!is_dir($dir)) {
        return ['created' => 0, 'removed' => 0, 'dups' => 0];
    }

    $ids = $pdo->query('SELECT id FROM products')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int) $id]);
    }
    $removed = count($ids);

    $cats = [];
    foreach ($pdo->query('SELECT id, slug FROM categories') as $c) {
        $cats[$c['slug']] = (int) $c['id'];
    }

    if (!isset($cats['colecao-atelier'])) {
        $pdo->prepare(
            'INSERT INTO categories (name, slug, image, description, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, 1)'
        )->execute([
            'Coleção Atelier',
            'colecao-atelier',
            '/uploads/moda/colecao/mariah-01.jpg',
            'Peças exclusivas Mariah Vendas',
            0,
        ]);
        $cats['colecao-atelier'] = (int) $pdo->lastInsertId();
    } else {
        $pdo->prepare('UPDATE categories SET name = ?, image = ? WHERE slug = ?')->execute([
            'Coleção Atelier',
            '/uploads/moda/colecao/mariah-01.jpg',
            'colecao-atelier',
        ]);
    }

    $looks = [
        ['vestidos', 'Vestido Sirena Noiva Branco com Aplicações'],
        ['vestidos', 'Vestido Longo Cetim Terracota com Fenda'],
        ['vestidos', 'Vestido Festa Vermelho Plissado'],
        ['vestidos', 'Vestido Longo Bordado Mesh Preto'],
        ['vestidos', 'Vestido Longo Amarelo Manteiga com Babados'],
        ['vestidos', 'Vestido Longo Magenta com Fenda'],
    ];

    $colors = [
        ['Off-white', '#F5F0E8'],
        ['Areia', '#C4A484'],
        ['Creme', '#E8DFD4'],
        ['Preto', '#1A1A1A'],
        ['Natural', '#D4C4B0'],
    ];
    $sizes = ['P', 'M', 'G', 'GG'];

    $insP = $pdo->prepare(
        'INSERT INTO products (category_id, name, slug, description, price, compare_at_price, is_active, is_featured, is_new, is_sale, is_bestseller, cost_price, sold_count)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
    );
    $insI = $pdo->prepare('INSERT INTO product_images (product_id, path, alt, sort_order) VALUES (?, ?, ?, 0)');
    $insV = $pdo->prepare('INSERT INTO product_variants (product_id, size, color, color_hex, sku, stock) VALUES (?, ?, ?, ?, ?, ?)');

    $n = 0;
    foreach ($looks as $i => $look) {
        $num = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
        $path = '/uploads/moda/colecao/mariah-' . $num . '.jpg';
        if (!is_file($root . '/public' . $path)) {
            continue;
        }
        $n++;
        [$type, $name] = $look;
        $catId = $cats[$type] ?? $cats['vestidos'];
        $slug = 'mariah-look-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        $price = [199.9, 229.9, 259.9, 289.9, 319.9, 349.9, 379.9][($n - 1) % 7];
        $featured = $n <= 8 ? 1 : 0;
        $isNew = $n <= 14 ? 1 : 0;
        $isSale = ($n % 6 === 0) ? 1 : 0;
        $best = ($n % 5 === 0) ? 1 : 0;
        $compare = $isSale ? round($price * 1.18, 2) : null;
        $cost = round($price * 0.4, 2);
        $sold = $best ? random_int(8, 30) : random_int(0, 7);
        $desc = "Peça exclusiva Mariah Vendas. {$name}. Acabamento artesanal e modelagem pensada para realçar a silhueta.";

        $insP->execute([$catId, $name, $slug, $desc, $price, $compare, $featured, $isNew, $isSale, $best, $cost, $sold]);
        $pid = (int) $pdo->lastInsertId();
        $insI->execute([$pid, $path, $name]);
        $color = $colors[($n - 1) % count($colors)];
        foreach ($sizes as $size) {
            $stock = $size === 'M' ? random_int(4, 12) : random_int(1, 8);
            $sku = 'MAR-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT) . "-{$size}";
            $insV->execute([$pid, $size, $color[0], $color[1], $sku, $stock]);
        }
    }

    $catImages = [
        'vestidos' => '/uploads/moda/colecao/mariah-01.jpg',
        'blusas' => '/uploads/moda/colecao/mariah-05.jpg',
        'calcas' => '/uploads/moda/colecao/mariah-02.jpg',
        'conjuntos' => '/uploads/moda/colecao/mariah-06.jpg',
        'saias' => '/uploads/moda/colecao/mariah-03.jpg',
        'jeans' => '/uploads/moda/colecao/mariah-04.jpg',
        'novidades' => '/uploads/moda/colecao/mariah-01.jpg',
        'promocoes' => '/uploads/moda/colecao/mariah-06.jpg',
        'colecao-atelier' => '/uploads/moda/colecao/mariah-02.jpg',
    ];
    $updCat = $pdo->prepare('UPDATE categories SET image = ? WHERE slug = ?');
    foreach ($catImages as $slug => $img) {
        if (is_file($root . '/public' . $img)) {
            $updCat->execute([$img, $slug]);
        }
    }

    $set = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $set->execute(['catalog_version', 'mariah-v2']);
    $set->execute(['hero_image', '/uploads/moda/hero-atelier.jpg']);
    $set->execute(['hero_title', 'Alta costura · Festa & Noiva']);
    $set->execute(['hero_subtitle', 'Vestidos exclusivos Mariah Vendas — modelagem impecável para momentos inesquecíveis.']);
    $ig = [];
    foreach ([1, 2, 3, 4, 5, 6] as $i) {
        $f = '/uploads/moda/colecao/mariah-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '.jpg';
        if (is_file($root . '/public' . $f)) {
            $ig[] = $f;
        }
    }
    $set->execute(['instagram_feed', json_encode($ig, JSON_UNESCAPED_SLASHES)]);

    return ['created' => $n, 'removed' => $removed, 'dups' => 0];
}
}

<?php

/**
 * Popula/atualiza a Coleção Bali (1 foto única por look).
 * Usado pelo seed inicial e pelo atualizador da Hostinger.
 *
 * @return array{created:int, removed:int, dups:int}
 */
if (!function_exists('mandalla_seed_bali_catalog')) {
function mandalla_seed_bali_catalog(PDO $pdo, string $root): array
{
    $manifestFile = $root . '/public/uploads/products/bali/manifest.json';
    if (!is_file($manifestFile)) {
        return ['created' => 0, 'removed' => 0, 'dups' => 0];
    }

    $manifest = json_decode((string) file_get_contents($manifestFile), true);
    if (!is_array($manifest)) {
        return ['created' => 0, 'removed' => 0, 'dups' => 0];
    }

    // Remove catálogo antigo (demo + bali anterior)
    $ids = $pdo->query('SELECT id FROM products')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int) $id]);
    }
    $removed = count($ids);

    $cats = [];
    foreach ($pdo->query('SELECT id, slug FROM categories') as $c) {
        $cats[$c['slug']] = (int) $c['id'];
    }

    // Garante categoria colecao-bali (filtro virtual usa slug bali-look-*)
    if (!isset($cats['colecao-bali'])) {
        $pdo->prepare(
            'INSERT INTO categories (name, slug, image, description, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, 1)'
        )->execute([
            'Coleção Bali',
            'colecao-bali',
            '/uploads/products/bali/bali-001.jpg',
            'Campanha Bali · Verão 27',
            0,
        ]);
        $cats['colecao-bali'] = (int) $pdo->lastInsertId();
    } else {
        $pdo->prepare('UPDATE categories SET image = ?, name = ? WHERE slug = ?')->execute([
            '/uploads/products/bali/bali-001.jpg',
            'Coleção Bali',
            'colecao-bali',
        ]);
    }

    // Nome amarrado à foto (chave = arquivo). Capas 001/002 e logo 086 ficam de fora.
    $looksByFile = [
        'bali-003.jpg' => ['vestidos', 'Vestido Longo Cru Bordado'],
        'bali-004.jpg' => ['vestidos', 'Vestido Cru Alça Contas'],
        'bali-005.jpg' => ['vestidos', 'Vestido Longo Margaridas'],
        'bali-006.jpg' => ['vestidos', 'Vestido Marfim Alça Laço'],
        'bali-007.jpg' => ['vestidos', 'Vestido Longo Branco Bordado'],
        'bali-008.jpg' => ['conjuntos', 'Macacão Azul Céu Flores'],
        'bali-009.jpg' => ['conjuntos', 'Macacão Tomara que Caia Azul'],
        'bali-010.jpg' => ['vestidos', 'Vestido Halter Crochê Branco'],
        'bali-011.jpg' => ['vestidos', 'Vestido Halter Flor Branca'],
        'bali-012.jpg' => ['vestidos', 'Vestido Midi Amarelo Corrente'],
        'bali-013.jpg' => ['vestidos', 'Vestido Midi Limão Faixa'],
        'bali-014.jpg' => ['vestidos', 'Vestido Amarelo Alça Dourada'],
        'bali-015.jpg' => ['conjuntos', 'Conjunto Crochê Branco Longo'],
        'bali-016.jpg' => ['conjuntos', 'Conjunto Halter Crochê Marfim'],
        'bali-017.jpg' => ['conjuntos', 'Conjunto Mini Branco Botões'],
        'bali-018.jpg' => ['conjuntos', 'Conjunto Corset Saia Off-White'],
        'bali-019.jpg' => ['vestidos', 'Vestido Midi Broderie Branco'],
        'bali-020.jpg' => ['vestidos', 'Vestido Broderie Borlas'],
        'bali-021.jpg' => ['vestidos', 'Vestido Longo Cinto Areia'],
        'bali-022.jpg' => ['vestidos', 'Vestido Corselete Fivela'],
        'bali-023.jpg' => ['conjuntos', 'Conjunto Top Bordado Saia Longa'],
        'bali-024.jpg' => ['conjuntos', 'Conjunto Cropped Floral Bordado'],
        'bali-025.jpg' => ['vestidos', 'Vestido Tubinho Pétala'],
        'bali-026.jpg' => ['vestidos', 'Vestido Curto Pétala Bordada'],
        'bali-027.jpg' => ['conjuntos', 'Conjunto Amarelo Pantalona'],
        'bali-028.jpg' => ['conjuntos', 'Conjunto Trespassado Limão'],
        'bali-029.jpg' => ['conjuntos', 'Conjunto Ombro Único Branco'],
        'bali-030.jpg' => ['conjuntos', 'Conjunto Ombro Único Botões'],
        'bali-031.jpg' => ['conjuntos', 'Conjunto Branco Botões Dourados'],
        'bali-032.jpg' => ['conjuntos', 'Conjunto Flor Amarelo Pantalona'],
        'bali-033.jpg' => ['conjuntos', 'Conjunto Cropped Flor Sol'],
        'bali-034.jpg' => ['vestidos', 'Vestido Midi Azul Céu'],
        'bali-035.jpg' => ['vestidos', 'Vestido Azul Bebê Recorte'],
        'bali-036.jpg' => ['conjuntos', 'Conjunto Ciganinha Saia Longa'],
        'bali-037.jpg' => ['conjuntos', 'Conjunto Ombro a Ombro Branco'],
        'bali-038.jpg' => ['conjuntos', 'Conjunto Ciganinha Bordado Bege'],
        'bali-039.jpg' => ['conjuntos', 'Conjunto Cru Saia Longa'],
        'bali-040.jpg' => ['conjuntos', 'Conjunto Cropped Saia Bordada'],
        'bali-041.jpg' => ['conjuntos', 'Conjunto Amarelo Alça Fina'],
        'bali-042.jpg' => ['conjuntos', 'Conjunto Cropped Amarelo Renda'],
        'bali-043.jpg' => ['conjuntos', 'Conjunto Limão Galão'],
        'bali-044.jpg' => ['vestidos', 'Vestido Midi Branco Fenda'],
        'bali-045.jpg' => ['vestidos', 'Vestido Alfaiataria Branco'],
        'bali-046.jpg' => ['conjuntos', 'Conjunto Coral Saia Longa'],
        'bali-047.jpg' => ['conjuntos', 'Conjunto Coral Borlas'],
        'bali-048.jpg' => ['conjuntos', 'Conjunto Salmão Floral Bordado'],
        'bali-049.jpg' => ['conjuntos', 'Macacão Amarelo Laço'],
        'bali-050.jpg' => ['conjuntos', 'Macacão Pantalona Borboleta'],
        'bali-051.jpg' => ['vestidos', 'Vestido Midi Amarelo Botões'],
        'bali-052.jpg' => ['vestidos', 'Vestido Amarelo Bolso Cinto'],
        'bali-053.jpg' => ['conjuntos', 'Conjunto Colete Pantalona Branco'],
        'bali-054.jpg' => ['conjuntos', 'Conjunto Colete Alfaiataria Off'],
        'bali-055.jpg' => ['conjuntos', 'Conjunto Cropped Zíper Branco'],
        'bali-056.jpg' => ['conjuntos', 'Conjunto Halter Flor Midi'],
        'bali-057.jpg' => ['conjuntos', 'Conjunto Halter Sol Amarelo'],
        'bali-058.jpg' => ['conjuntos', 'Conjunto Halter Flor Bordada'],
        'bali-059.jpg' => ['vestidos', 'Vestido Curto Flor Pêssego'],
        'bali-060.jpg' => ['vestidos', 'Vestido Mini Coral Rosa'],
        'bali-061.jpg' => ['conjuntos', 'Conjunto Halter Laço Baunilha'],
        'bali-062.jpg' => ['conjuntos', 'Conjunto Halter Plissado Baunilha'],
        'bali-063.jpg' => ['conjuntos', 'Conjunto Cru Decote V'],
        'bali-064.jpg' => ['conjuntos', 'Conjunto Linho Manga Curta'],
        'bali-065.jpg' => ['vestidos', 'Vestido Longo Estampa Laranja'],
        'bali-066.jpg' => ['vestidos', 'Vestido Tangerina Costas Amarradas'],
        'bali-067.jpg' => ['conjuntos', 'Conjunto Short Flor Branco'],
        'bali-068.jpg' => ['conjuntos', 'Conjunto Cropped Short Bolsos'],
        'bali-069.jpg' => ['conjuntos', 'Conjunto Amarelo Detalhe Folha'],
        'bali-070.jpg' => ['conjuntos', 'Conjunto Areia Alça Amarração'],
        'bali-071.jpg' => ['conjuntos', 'Conjunto Cropped Crochê Areia'],
        'bali-072.jpg' => ['conjuntos', 'Conjunto Pêssego Cinto'],
        'bali-073.jpg' => ['conjuntos', 'Conjunto Pêssego Bolsos'],
        'bali-074.jpg' => ['conjuntos', 'Conjunto Zíper Saia Mini'],
        'bali-075.jpg' => ['conjuntos', 'Conjunto Branco Zíper Dourado'],
        'bali-076.jpg' => ['conjuntos', 'Conjunto Colete Linho Bege'],
        'bali-077.jpg' => ['conjuntos', 'Conjunto Colete Costas Botões'],
        'bali-078.jpg' => ['conjuntos', 'Conjunto Tomara que Caia Amarelo'],
        'bali-079.jpg' => ['conjuntos', 'Conjunto Amarelo Laço Cintura'],
        'bali-080.jpg' => ['vestidos', 'Vestido Midi Pêssego Botões'],
        'bali-081.jpg' => ['vestidos', 'Vestido Midi Abotoado Damasco'],
        'bali-082.jpg' => ['conjuntos', 'Conjunto Short Azul Céu'],
        'bali-083.jpg' => ['conjuntos', 'Conjunto Short Azul Flor'],
        'bali-084.jpg' => ['conjuntos', 'Conjunto Branco Calça Reta'],
        'bali-085.jpg' => ['conjuntos', 'Conjunto Renda Branco Fivela'],
    ];
    $colors = [
        ['Off-white', '#F5F0E8'],
        ['Areia', '#C4A484'],
        ['Creme', '#E8DFD4'],
        ['Natural', '#D4C4B0'],
        ['Branco', '#FFFFFF'],
        ['Amarelo', '#E8C84A'],
        ['Azul céu', '#A7C7E7'],
    ];
    $sizes = ['P', 'M', 'G', 'GG'];

    $insP = $pdo->prepare(
        'INSERT INTO products (category_id, name, slug, description, price, compare_at_price, is_active, is_featured, is_new, is_sale, is_bestseller, cost_price, sold_count)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
    );
    $insI = $pdo->prepare('INSERT INTO product_images (product_id, path, alt, sort_order) VALUES (?, ?, ?, 0)');
    $insV = $pdo->prepare('INSERT INTO product_variants (product_id, size, color, color_hex, sku, stock) VALUES (?, ?, ?, ?, ?, ?)');

    $seenHash = [];
    $n = 0;
    foreach ($manifest as $item) {
        $idx = (int) ($item['index'] ?? 0);
        // capas + logo final
        if ($idx <= 2 || $idx >= 86) {
            continue;
        }
        $path = (string) ($item['file'] ?? '');
        $file = basename($path);
        $abs = $root . '/public' . $path;
        if ($path === '' || !is_file($abs)) {
            continue;
        }
        $hash = md5_file($abs);
        if (isset($seenHash[$hash])) {
            continue;
        }
        $seenHash[$hash] = true;

        if (!isset($looksByFile[$file])) {
            continue;
        }
        $look = $looksByFile[$file];
        $n++;
        $type = $look[0];
        $name = $look[1];
        if (!isset($cats[$type])) {
            $type = 'vestidos';
        }
        $catId = $cats[$type];
        $slug = 'bali-look-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        $price = [189.9, 219.9, 249.9, 279.9, 299.9, 329.9, 359.9][($n - 1) % 7];
        $featured = $n <= 8 ? 1 : 0;
        $isNew = $n <= 16 ? 1 : 0;
        $isSale = ($n % 5 === 0) ? 1 : 0;
        $best = ($n % 4 === 0) ? 1 : 0;
        $compare = $isSale ? round($price * 1.2, 2) : null;
        $cost = round($price * 0.42, 2);
        $sold = $best ? random_int(10, 35) : random_int(0, 8);
        $desc = "Peça da Coleção Bali — Verão 27. {$name}.";

        $insP->execute([$catId, $name, $slug, $desc, $price, $compare, $featured, $isNew, $isSale, $best, $cost, $sold]);
        $pid = (int) $pdo->lastInsertId();
        $insI->execute([$pid, $path, $name]);
        $color = $colors[($n - 1) % count($colors)];
        foreach ($sizes as $size) {
            $stock = $size === 'M' ? random_int(5, 12) : random_int(2, 8);
            $sku = 'BALI-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT) . "-{$size}";
            $insV->execute([$pid, $size, $color[0], $color[1], $sku, $stock]);
        }
    }

    // Atualiza imagens de categorias principais com fotos Bali
    $catImages = [
        'vestidos' => '/uploads/products/bali/bali-003.jpg',
        'blusas' => '/uploads/products/bali/bali-010.jpg',
        'calcas' => '/uploads/products/bali/bali-020.jpg',
        'conjuntos' => '/uploads/products/bali/bali-008.jpg',
        'saias' => '/uploads/products/bali/bali-015.jpg',
        'novidades' => '/uploads/products/bali/bali-005.jpg',
        'promocoes' => '/uploads/products/bali/bali-012.jpg',
    ];
    $updCat = $pdo->prepare('UPDATE categories SET image = ? WHERE slug = ?');
    foreach ($catImages as $slug => $img) {
        if (is_file($root . '/public' . $img)) {
            $updCat->execute([$img, $slug]);
        }
    }

    $set = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $set->execute(['catalog_version', 'bali-v3']);
    $set->execute(['hero_image', '/uploads/products/bali/bali-001.jpg']);
    $set->execute(['hero_title', 'Coleção Bali']);
    $set->execute(['hero_subtitle', 'Verão 27 — leveza, texturas artesanais e tons neutros inspirados em Bali.']);
    $ig = [];
    foreach ([3, 8, 15, 25, 40, 55] as $i) {
        $f = '/uploads/products/bali/bali-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '.jpg';
        if (is_file($root . '/public' . $f)) {
            $ig[] = $f;
        }
    }
    $set->execute(['instagram_feed', json_encode($ig, JSON_UNESCAPED_SLASHES)]);

    $hashes = [];
    $dups = 0;
    foreach ($pdo->query('SELECT path FROM product_images WHERE sort_order = 0') as $r) {
        $file = $root . '/public' . $r['path'];
        if (!is_file($file)) {
            continue;
        }
        $h = md5_file($file);
        if (isset($hashes[$h])) {
            $dups++;
        }
        $hashes[$h] = true;
    }

    return ['created' => $n, 'removed' => $removed, 'dups' => $dups];
}
} // function_exists

<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Database;

$pdo = Database::connection();
$schema = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schema);

$settings = [
    'store_name' => 'Mariah Vendas',
    'tagline' => 'Moda feminina e peças exclusivas para você brilhar',
    'whatsapp' => '5511999999999',
    'instagram' => '@mariah.vendas',
    'email' => 'contato@mariahvendas.com.br',
    'phone' => '(11) 99999-9999',
    'address' => 'Atelier · Enviamos para todo o Brasil',
    'hero_title' => 'Elegância costurada nos detalhes',
    'hero_subtitle' => 'Peças exclusivas, tecidos nobres e modelagem pensada para valorizar você em cada ocasião.',
    'hero_image' => '/uploads/moda/hero-atelier.jpg',
    'exchange_policy' => 'Trocas em até 7 dias com etiqueta e sem uso.',
    'privacy_policy' => 'Seus dados são tratados conforme a LGPD e usados apenas para atendimento e entrega.',
    'delivery_info' => 'Entregamos para todo o Brasil. O prazo varia conforme a modalidade escolhida.',
    'instagram_feed' => json_encode([
        '/uploads/moda/colecao/mariah-01.jpg',
        '/uploads/moda/colecao/mariah-02.jpg',
        '/uploads/moda/colecao/mariah-03.jpg',
        '/uploads/moda/colecao/mariah-04.jpg',
        '/uploads/moda/colecao/mariah-05.jpg',
        '/uploads/moda/colecao/mariah-06.jpg',
    ]),
];

$insSetting = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
foreach ($settings as $k => $v) {
    $insSetting->execute([$k, $v]);
}

$pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)')->execute([
    'Administradora',
    'admin@mariahvendas.com.br',
    '11999999999',
    password_hash('admin123', PASSWORD_DEFAULT),
    'admin',
]);

$pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)')->execute([
    'Cliente Exemplo',
    'cliente@exemplo.com',
    '11988887777',
    password_hash('cliente123', PASSWORD_DEFAULT),
    'customer',
]);

$categories = [
    ['Vestidos', 'vestidos', 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&w=800&q=80', 1],
    ['Blusas', 'blusas', 'https://images.unsplash.com/photo-1564257631407-4deb1f99d992?auto=format&fit=crop&w=800&q=80', 2],
    ['Calças', 'calcas', 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?auto=format&fit=crop&w=800&q=80', 3],
    ['Conjuntos', 'conjuntos', 'https://images.unsplash.com/photo-1581044777550-4cfa60707c03?auto=format&fit=crop&w=800&q=80', 4],
    ['Saias', 'saias', 'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?auto=format&fit=crop&w=800&q=80', 5],
    ['Jeans', 'jeans', 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=800&q=80', 6],
    ['Acessórios', 'acessorios', 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80', 7],
    ['Promoções', 'promocoes', 'https://images.unsplash.com/photo-1441984904996-e0b6ba18a55a?auto=format&fit=crop&w=800&q=80', 8],
    ['Novidades', 'novidades', 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=800&q=80', 9],
];

$catStmt = $pdo->prepare('INSERT INTO categories (name, slug, image, description, sort_order) VALUES (?, ?, ?, ?, ?)');
$catIds = [];
foreach ($categories as $i => $c) {
    $catStmt->execute([$c[0], $c[1], $c[2], 'Categoria demonstrativa: ' . $c[0], $c[3]]);
    $catIds[$c[1]] = (int) $pdo->lastInsertId();
}

$products = [
    [
        'cat' => 'vestidos', 'name' => 'Vestido Midi Areia', 'price' => 289.90, 'compare' => 349.90,
        'featured' => 1, 'new' => 1, 'sale' => 1, 'best' => 1, 'cost' => 120,
        'desc' => 'Vestido midi em linho misto, caimento fluido e alças finas. Ideal para dias quentes com elegância.',
        'images' => [
            'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=900&q=80',
        ],
        'colors' => [['Areia', '#C4A484'], ['Preto', '#1A1A1A']],
    ],
    [
        'cat' => 'blusas', 'name' => 'Blusa Cetim Nude', 'price' => 179.90, 'compare' => null,
        'featured' => 1, 'new' => 1, 'sale' => 0, 'best' => 1, 'cost' => 70,
        'desc' => 'Blusa em cetim com brilho sutil, decote V e manga curta. Peça versátil para trabalho e lazer.',
        'images' => [
            'https://images.unsplash.com/photo-1564257631407-4deb1f99d992?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?auto=format&fit=crop&w=900&q=80',
        ],
        'colors' => [['Nude', '#D2B48C'], ['Off-white', '#F5F0E8']],
    ],
    [
        'cat' => 'calcas', 'name' => 'Calça Wide Leg Creme', 'price' => 249.90, 'compare' => null,
        'featured' => 1, 'new' => 0, 'sale' => 0, 'best' => 1, 'cost' => 95,
        'desc' => 'Calça wide leg de alfaiataria leve, cintura alta e caimento solto. Conforto com presença.',
        'images' => [
            'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?auto=format&fit=crop&w=900&q=80',
        ],
        'colors' => [['Creme', '#F5F0E8'], ['Preto', '#1A1A1A']],
    ],
    [
        'cat' => 'conjuntos', 'name' => 'Conjunto Linho Natural', 'price' => 329.90, 'compare' => 389.90,
        'featured' => 1, 'new' => 1, 'sale' => 1, 'best' => 0, 'cost' => 140,
        'desc' => 'Conjunto em linho com top e calça coordenados. Textura natural e visual contemporâneo.',
        'images' => [
            'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1509631179647-0177331693ae?auto=format&fit=crop&w=900&q=80',
        ],
        'colors' => [['Natural', '#E8DFD4']],
    ],
    [
        'cat' => 'saias', 'name' => 'Saia Midi Plissada', 'price' => 199.90, 'compare' => null,
        'featured' => 0, 'new' => 1, 'sale' => 0, 'best' => 0, 'cost' => 80,
        'desc' => 'Saia midi plissada com cintura elástica discreta. Movimento leve e silhueta feminina.',
        'images' => [
            'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?auto=format&fit=crop&w=900&q=80',
        ],
        'colors' => [['Bege', '#D4C4B0'], ['Preto', '#1A1A1A']],
    ],
    [
        'cat' => 'jeans', 'name' => 'Jeans Mom Lavagem Clara', 'price' => 219.90, 'compare' => 259.90,
        'featured' => 0, 'new' => 0, 'sale' => 1, 'best' => 1, 'cost' => 90,
        'desc' => 'Jeans mom com lavagem clara e modelagem confortável. Essencial do guarda-roupa.',
        'images' => [
            'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=900&q=80',
        ],
        'colors' => [['Azul claro', '#A7C7E7']],
    ],
    [
        'cat' => 'blusas', 'name' => 'Camisa Oversized Off', 'price' => 189.90, 'compare' => null,
        'featured' => 1, 'new' => 0, 'sale' => 0, 'best' => 0, 'cost' => 75,
        'desc' => 'Camisa oversized em tricoline off-white. Use aberta, fechada ou amarrada na cintura.',
        'images' => [
            'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=900&q=80',
        ],
        'colors' => [['Off-white', '#F5F0E8']],
    ],
    [
        'cat' => 'vestidos', 'name' => 'Vestido Longo Preto', 'price' => 359.90, 'compare' => null,
        'featured' => 1, 'new' => 0, 'sale' => 0, 'best' => 1, 'cost' => 150,
        'desc' => 'Vestido longo preto com decote suave e fenda lateral. Peça de presença para a noite.',
        'images' => [
            'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?auto=format&fit=crop&w=900&q=80',
        ],
        'colors' => [['Preto', '#1A1A1A']],
    ],
];

$sizes = ['P', 'M', 'G', 'GG'];
$pStmt = $pdo->prepare(
    'INSERT INTO products (category_id, name, slug, description, price, compare_at_price, is_active, is_featured, is_new, is_sale, is_bestseller, cost_price, sold_count)
     VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
);
$imgStmt = $pdo->prepare('INSERT INTO product_images (product_id, path, alt, sort_order) VALUES (?, ?, ?, ?)');
$vStmt = $pdo->prepare('INSERT INTO product_variants (product_id, size, color, color_hex, sku, stock) VALUES (?, ?, ?, ?, ?, ?)');

foreach ($products as $index => $p) {
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $p['name'])));
    $sold = $p['best'] ? rand(12, 40) : rand(0, 8);
    $pStmt->execute([
        $catIds[$p['cat']],
        $p['name'],
        trim($slug, '-'),
        $p['desc'] . ' (Produto demonstrativo de exemplo.)',
        $p['price'],
        $p['compare'],
        $p['featured'],
        $p['new'],
        $p['sale'],
        $p['best'],
        $p['cost'],
        $sold,
    ]);
    $pid = (int) $pdo->lastInsertId();

    foreach ($p['images'] as $i => $img) {
        $imgStmt->execute([$pid, $img, $p['name'], $i]);
    }

    foreach ($p['colors'] as $color) {
        foreach ($sizes as $size) {
            $stock = rand(0, 12);
            if ($size === 'M') {
                $stock = max(3, $stock);
            }
            $sku = 'MD-' . strtoupper(substr($slug, 0, 4)) . "-{$size}-" . substr(md5($color[0]), 0, 4);
            $vStmt->execute([$pid, $size, $color[0], $color[1], $sku, $stock]);
            $vid = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO stock_movements (variant_id, type, quantity, note) VALUES (?, ?, ?, ?)')
                ->execute([$vid, 'in', $stock, 'Estoque inicial (demo)']);
        }
    }
}

$pdo->exec("INSERT INTO shipping_methods (name, description, price, estimated_days) VALUES
 ('Retirada na loja', 'Retire sem custo no showroom', 0, '1-2 dias'),
 ('Sedex', 'Entrega expressa', 29.90, '2-4 dias úteis'),
 ('PAC', 'Entrega econômica', 18.90, '5-10 dias úteis')");

$pdo->exec("INSERT INTO payment_methods (name, code, instructions) VALUES
 ('PIX', 'pix', 'Após confirmar o pedido, enviaremos a chave PIX pelo WhatsApp.'),
 ('Cartão (link de pagamento)', 'card_link', 'Enviaremos um link seguro de pagamento. Não armazenamos dados de cartão.'),
 ('Pagamento na entrega / retirada', 'on_delivery', 'Disponível conforme combinado no atendimento.')");

$pdo->exec("INSERT INTO coupons (code, type, value, min_subtotal, is_active) VALUES
 ('MARIAH10', 'percent', 10, 150, 1),
 ('BEMVINDA', 'fixed', 30, 200, 1)");

$sampleImage = dirname(__DIR__) . '/public/uploads/products/vestido-midi-areia-0.jpg';
if (!file_exists($sampleImage)) {
    require __DIR__ . '/download_images.php';
}

require_once __DIR__ . '/seed_mariah_catalog.php';
$mariah = mariah_seed_catalog($pdo, dirname(__DIR__));

echo "Banco Mariah Vendas criado. Coleção: {$mariah['created']} produtos.\n";

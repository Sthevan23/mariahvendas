<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$pdo = App\Core\Database::connection();
$settings = [
    'store_name' => 'Ateliê Mariah',
    'tagline' => 'Vestidos de festa exclusivos — formatura, debutante e eventos',
    'instagram' => '@atelie_mariahaltacostura_',
    'og_image' => '/assets/brand/og-share.jpg',
    'email' => 'contato@mariahvendas.com.br',
];
$stmt = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
foreach ($settings as $key => $value) {
    $stmt->execute([$key, $value]);
}

$pdo->prepare('UPDATE users SET email = ? WHERE email = ? AND role = ?')
    ->execute(['admin@mariahvendas.com.br', 'admin@mariahaltocostura.com.br', 'admin']);

echo "Marca atualizada para Ateliê Mariah.\n";

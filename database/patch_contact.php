<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$pdo = App\Core\Database::connection();
$settings = [
    'whatsapp' => '5537998086607',
    'phone' => '(37) 99808-6607',
    'address' => 'Rua Minas Gerais, 757, Centro — Divinópolis/MG',
    'instagram' => '@atelie_mariahaltacostura_',
];
$stmt = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
foreach ($settings as $key => $value) {
    $stmt->execute([$key, $value]);
}

echo "Contatos atualizados.\n";

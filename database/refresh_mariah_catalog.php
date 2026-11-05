<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require __DIR__ . '/seed_mariah_catalog.php';

$pdo = App\Core\Database::connection();
$result = mariah_seed_catalog($pdo, dirname(__DIR__));
echo "Atualizado: {$result['created']} produtos.\n";

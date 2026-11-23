<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$router = require dirname(__DIR__) . '/routes.php';
\App\Services\Analytics::track();
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/');

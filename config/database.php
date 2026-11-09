<?php

// Banco MySQL. Credenciais vêm do .env (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS).
return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'name' => getenv('DB_NAME') ?: '',
    'user' => getenv('DB_USER') ?: '',
    'pass' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];

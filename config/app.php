<?php

return [
    'name' => 'Mariah Vendas',
    'tagline' => 'Moda feminina e peças exclusivas para você brilhar',
    'url' => getenv('APP_URL') ?: 'http://localhost:8080',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => (getenv('APP_DEBUG') ?: 'true') === 'true',
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    'currency' => 'BRL',
    'whatsapp' => getenv('WHATSAPP') ?: '5511999999999',
    'low_stock_threshold' => 5,
    'session_name' => 'mariah_vendas_session',
    'logo_path' => '/assets/brand/logo.jpg',
];

<?php

return [
    'name' => 'Ateliê Mariah',
    'tagline' => 'Vestidos de festa exclusivos — formatura, debutante e eventos',
    'url' => getenv('APP_URL') ?: 'http://localhost:8080',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => (getenv('APP_DEBUG') ?: 'false') === 'true',
    'timezone' => 'America/Sao_Paulo',
    'locale' => 'pt_BR',
    'currency' => 'BRL',
    'whatsapp' => getenv('WHATSAPP') ?: '5537998086607',
    'low_stock_threshold' => 3,
    // Frete desligado por enquanto: ao ativar, o checkout volta a exigir a forma de entrega e somar o frete.
    'shipping_enabled' => false,
    'session_name' => 'mariah_vendas_session',
    'logo_path' => '/assets/brand/logo-header.png',
];

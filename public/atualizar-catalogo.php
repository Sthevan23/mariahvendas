<?php

/**
 * Atualiza o catálogo na Hostinger com a Coleção Bali (fotos novas).
 * Abra UMA VEZ e depois APAGUE este arquivo.
 *
 * URL: https://SEU-DOMINIO/atualizar-catalogo.php?key=mandalla-bali-2026
 */
declare(strict_types=1);

$key = $_GET['key'] ?? '';
if ($key !== 'mandalla-bali-2026') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Negado. Use ?key=mandalla-bali-2026\n");
}

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/database/seed_bali_catalog.php';

use App\Core\Database;

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = Database::connection();
    $result = mandalla_seed_bali_catalog($pdo, dirname(__DIR__));
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Erro</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Catálogo atualizado — Mandalla</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 560px; margin: 3rem auto; padding: 0 1rem; background: #f7f3ee; color: #1a1a1a; }
    .box { background: #fffcf8; border: 1px solid rgba(26,26,26,.1); padding: 1.5rem; }
    a { color: #1a1a1a; }
    strong { color: #a00; }
  </style>
</head>
<body>
  <div class="box">
    <h1>Catálogo Bali atualizado</h1>
    <p>Removidos: <?= (int) $result['removed'] ?> produtos antigos</p>
    <p>Criados: <?= (int) $result['created'] ?> looks com foto única</p>
    <p>Duplicatas: <?= (int) $result['dups'] ?></p>
    <p><a href="/">Ver loja</a> · <a href="/categoria/colecao-bali">Coleção Bali</a> · <a href="/admin">Admin</a></p>
    <p><strong>Apague agora o arquivo</strong> <code>public/atualizar-catalogo.php</code> no File Manager.</p>
  </div>
</body>
</html>

<?php

/**
 * Corrige login admin na Hostinger (senha/e-mail no SQLite).
 * Abra UMA VEZ: https://SEU-DOMINIO/setup-admin.php?key=mariah-admin-2026
 * Depois apague este arquivo.
 */
declare(strict_types=1);

$key = $_GET['key'] ?? '';
if ($key !== 'mariah-admin-2026') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Negado.\n");
}

require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/database/ensure_admin.php';
$msg = mariah_ensure_admin();

require_once dirname(__DIR__) . '/database/patch_stock_levels.php';
$stockMsg = mariah_patch_stock_levels();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Admin configurado</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 520px; margin: 3rem auto; padding: 0 1rem; }
    code { background: #f3efe9; padding: 0.15rem 0.4rem; }
  </style>
</head>
<body>
  <h1>Login admin atualizado</h1>
  <p><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
  <?php if ($stockMsg !== ''): ?><p><small><?= htmlspecialchars($stockMsg, ENT_QUOTES, 'UTF-8') ?></small></p><?php endif; ?>
  <p>E-mail: <code>admin@mariahvendas.com.br</code></p>
  <p>Senha: <code>mariah123</code> (troque em Minha conta)</p>
  <p><a href="/admin/login">Ir para o painel</a></p>
  <p><strong>Apague</strong> o arquivo <code>public/setup-admin.php</code> do servidor.</p>
</body>
</html>

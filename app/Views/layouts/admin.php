<?php
use App\Core\Helpers;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$flash = Helpers::flash();
if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= Helpers::e(($title ?? 'Admin') . ' | Mariah Admin') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@450;500&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="admin-body">
<?php if ($flash): ?><div class="flash <?= Helpers::e($flash['type']) ?>" data-flash><?= Helpers::e($flash['message']) ?></div><?php endif; ?>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a class="logo" href="/admin" style="display:block;margin-bottom:1.5rem;color:#fff">Mariah Vendas</a>
    <a href="/admin" class="<?= $path === '/admin' ? 'active' : '' ?>">Dashboard</a>
    <a href="/admin/produtos" class="<?= str_starts_with($path, '/admin/produtos') ? 'active' : '' ?>">Produtos</a>
    <a href="/admin/pedidos" class="<?= str_starts_with($path, '/admin/pedidos') ? 'active' : '' ?>">Pedidos</a>
    <a href="/admin/estoque" class="<?= str_starts_with($path, '/admin/estoque') ? 'active' : '' ?>">Estoque</a>
    <a href="/admin/financeiro" class="<?= str_starts_with($path, '/admin/financeiro') ? 'active' : '' ?>">Financeiro</a>
    <a href="/admin/clientes" class="<?= str_starts_with($path, '/admin/clientes') ? 'active' : '' ?>">Clientes</a>
    <a href="/admin/configuracoes" class="<?= str_starts_with($path, '/admin/configuracoes') ? 'active' : '' ?>">Configurações</a>
    <a href="/" target="_blank">Ver loja</a>
    <form method="post" action="/admin/logout" style="margin-top:1rem">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf']) ?>">
      <button class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.3)" type="submit">Sair</button>
    </form>
  </aside>
  <main class="admin-main"><?= $content ?></main>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>

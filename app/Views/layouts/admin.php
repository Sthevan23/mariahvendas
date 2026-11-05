<?php
use App\Core\Auth;
use App\Core\Helpers;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$flash = Helpers::flash();
$user = Auth::user();
$nav = static function (string $prefix) use ($path): string {
    return ($path === $prefix || str_starts_with($path, $prefix . '/')) ? 'active' : '';
};
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= Helpers::e(($title ?? 'Admin') . ' | Ateliê Mariah') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@450;500&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css?v=15">
  <link rel="stylesheet" href="/assets/css/admin.css?v=1">
</head>
<body class="admin-body">
<?php if ($flash): ?><div class="flash <?= Helpers::e($flash['type']) ?>" data-flash><?= Helpers::e($flash['message']) ?></div><?php endif; ?>

<div class="admin-sidebar-backdrop" id="admin-sidebar-backdrop" aria-hidden="true"></div>

<div class="admin-shell">
  <header class="admin-topbar">
    <button type="button" class="admin-nav-toggle" id="admin-nav-toggle" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Abrir menu">☰</button>
    <span class="admin-topbar-title">Painel</span>
    <a href="/" target="_blank" rel="noopener" style="color:rgba(255,255,255,.75);font-size:.85rem">Loja</a>
  </header>

  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar-head">
      <a class="logo-mark" href="/admin">
        <img src="/assets/brand/logo-header.png" alt="">
        <span>
          <strong>Ateliê Mariah</strong>
          <small>Painel admin</small>
        </span>
      </a>
    </div>

    <p class="admin-nav-label">Visão geral</p>
    <a href="/admin" class="<?= $path === '/admin' ? 'active' : '' ?>">Dashboard</a>

    <p class="admin-nav-label">Loja</p>
    <a href="/admin/produtos" class="<?= $nav('/admin/produtos') ?>">Produtos</a>
    <a href="/admin/pedidos" class="<?= $nav('/admin/pedidos') ?>">Pedidos</a>
    <a href="/admin/estoque" class="<?= $nav('/admin/estoque') ?>">Estoque</a>
    <a href="/admin/clientes" class="<?= $nav('/admin/clientes') ?>">Clientes</a>

    <p class="admin-nav-label">Gestão</p>
    <a href="/admin/financeiro" class="<?= $nav('/admin/financeiro') ?>">Financeiro</a>
    <a href="/admin/configuracoes" class="<?= $nav('/admin/configuracoes') ?>">Configurações</a>
    <a href="/admin/conta" class="<?= $nav('/admin/conta') ?>">Minha conta</a>

    <div class="admin-sidebar-foot">
      <?php if ($user): ?>
        <p style="color:rgba(255,255,255,.55);font-size:.8rem;margin:0 0 .75rem;padding:0 .5rem"><?= Helpers::e($user['name']) ?></p>
      <?php endif; ?>
      <a href="/" target="_blank" rel="noopener">Ver loja ↗</a>
      <form method="post" action="/admin/logout" style="margin-top:.75rem">
        <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
        <button class="btn btn-outline" type="submit">Sair</button>
      </form>
    </div>
  </aside>

  <main class="admin-main"><?= $content ?></main>
</div>
<script src="/assets/js/app.js?v=5" defer></script>
</body>
</html>

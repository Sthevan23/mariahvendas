<?php
use App\Core\Auth;
use App\Core\Helpers;
$store = Helpers::setting('store_name', 'Mariah Vendas');
$flash = Helpers::flash();
$cartCount = Helpers::cartCount();
$favCount = count($_SESSION['favorites'] ?? []);
if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['_csrf'];
$whatsapp = Helpers::whatsappLink('Olá! Gostaria de atendimento da ' . $store . '.');
$logo = '/assets/brand/logo.jpg';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= Helpers::e(($title ?? $store) . ' | ' . $store) ?></title>
  <meta name="description" content="<?= Helpers::e($meta_description ?? Helpers::setting('tagline', 'Moda feminina premium')) ?>">
  <meta property="og:title" content="<?= Helpers::e($title ?? $store) ?>">
  <meta property="og:description" content="<?= Helpers::e($meta_description ?? Helpers::setting('tagline')) ?>">
  <?php if (!empty($og_image)): ?><meta property="og:image" content="<?= Helpers::e($og_image) ?>"><?php endif; ?>
  <link rel="icon" href="<?= Helpers::e($logo) ?>" type="image/jpeg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css?v=2">
  <script type="application/ld+json">
  <?= json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'ClothingStore',
      'name' => $store,
      'description' => Helpers::setting('tagline'),
      'telephone' => Helpers::setting('phone'),
      'email' => Helpers::setting('email'),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
  </script>
</head>
<body>
<div class="top-bar">
  <div class="container top-bar-inner">
    <span>Mariah Vendas · Atendimento personalizado</span>
    <a href="<?= Helpers::e($whatsapp) ?>" target="_blank" rel="noopener">Fale conosco no WhatsApp</a>
  </div>
</div>
<header class="site-header" data-header>
  <div class="container header-inner">
    <button class="icon-btn menu-toggle" type="button" data-menu-toggle aria-label="Menu">
      <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>
    <a class="logo logo-brand" href="/">
      <img src="<?= Helpers::e($logo) ?>" alt="<?= Helpers::e($store) ?>" width="56" height="56">
      <span class="logo-text"><?= Helpers::e($store) ?></span>
    </a>
    <nav class="nav-desktop" aria-label="Principal">
      <a href="/">Início</a>
      <a href="/categoria/colecao-atelier">Atelier</a>
      <a href="/categoria/novidades">Novidades</a>
      <a href="/catalogo">Roupas</a>
      <a href="/categoria/vestidos">Vestidos</a>
      <a href="/categoria/conjuntos">Conjuntos</a>
      <a href="/categoria/calcas">Calças</a>
      <a href="/categoria/blusas">Blusas</a>
      <a href="/categoria/promocoes">Promoções</a>
    </nav>
    <div class="header-actions">
      <button class="icon-btn" type="button" data-search-toggle aria-label="Pesquisar">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
      </button>
      <a class="icon-btn" href="/favoritos" aria-label="Favoritos">
        <svg viewBox="0 0 24 24"><path d="M12 21s-7-4.5-9.5-8.5C.5 9 2.5 5 6.2 5c2 0 3.3 1.2 3.8 2 .5-.8 1.8-2 3.8-2 3.7 0 5.7 4 3.7 7.5C19 16.5 12 21 12 21z"/></svg>
        <?php if ($favCount): ?><span class="badge"><?= $favCount ?></span><?php endif; ?>
      </a>
      <a class="icon-btn" href="/carrinho" aria-label="Sacola" data-bag-icon>
        <svg viewBox="0 0 24 24"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg>
        <span class="badge" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span>
      </a>
      <a class="icon-btn" href="<?= Auth::check() ? '/conta' : '/login' ?>" aria-label="Conta">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 19a7 7 0 0 1 14 0"/></svg>
      </a>
    </div>
  </div>
  <div class="search-panel" data-search-panel>
    <form action="/busca" method="get">
      <input type="search" name="q" placeholder="Buscar peças..." aria-label="Buscar">
      <button class="btn" type="submit">Buscar</button>
    </form>
  </div>
</header>

<nav class="mobile-nav" data-mobile-nav aria-label="Mobile">
  <a href="/">Início</a>
  <a href="/categoria/colecao-atelier">Coleção Atelier</a>
  <a href="/categoria/novidades">Novidades</a>
  <a href="/catalogo">Roupas</a>
  <a href="/categoria/vestidos">Vestidos</a>
  <a href="/categoria/conjuntos">Conjuntos</a>
  <a href="/categoria/calcas">Calças</a>
  <a href="/categoria/blusas">Blusas</a>
  <a href="/categoria/promocoes">Promoções</a>
  <a href="/favoritos">Favoritos</a>
  <a href="/conta">Minha conta</a>
</nav>

<?php if ($flash): ?>
  <div class="flash <?= Helpers::e($flash['type']) ?>" data-flash><?= Helpers::e($flash['message']) ?></div>
<?php endif; ?>

<main><?= $content ?></main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <a class="logo logo-brand logo-brand--footer" href="/">
        <img src="<?= Helpers::e($logo) ?>" alt="" width="48" height="48">
        <span class="logo-text"><?= Helpers::e($store) ?></span>
      </a>
      <p><?= Helpers::e(Helpers::setting('tagline')) ?></p>
      <p><?= Helpers::e(Helpers::setting('address')) ?></p>
    </div>
    <div>
      <h4>Loja</h4>
      <a href="/catalogo">Catálogo</a>
      <a href="/categoria/novidades">Novidades</a>
      <a href="/categoria/promocoes">Promoções</a>
      <a href="/favoritos">Favoritos</a>
    </div>
    <div>
      <h4>Ajuda</h4>
      <a href="/trocas">Trocas</a>
      <a href="/privacidade">Privacidade</a>
      <a href="<?= Helpers::e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a>
    </div>
    <div>
      <h4>Contato</h4>
      <p><?= Helpers::e(Helpers::setting('email')) ?></p>
      <p><?= Helpers::e(Helpers::setting('phone')) ?></p>
      <p><?= Helpers::e(Helpers::setting('instagram')) ?></p>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>© <?= date('Y') ?> <?= Helpers::e($store) ?>. Todos os direitos reservados.</span>
    <span>Mariah Vendas · Moda feminina</span>
  </div>
</footer>

<a class="wa-float" href="<?= Helpers::e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.7 14.9L2 22l5.3-1.4A10 10 0 1 0 12 2zm0 2a8 8 0 0 1 6.7 12.3l-.3.4.8 2.9-3-.8-.4.2A8 8 0 1 1 12 4zm4.2 9.6c-.2-.1-1.3-.6-1.5-.7s-.3-.1-.5.1-.6.7-.7.8-.3.2-.5.1a6.4 6.4 0 0 1-1.9-1.2 7.1 7.1 0 0 1-1.3-1.6c-.1-.2 0-.3.1-.4l.3-.4.1-.2a.5.5 0 0 0 0-.5c0-.1-.5-1.2-.7-1.6s-.4-.4-.5-.4h-.4a.8.8 0 0 0-.6.3 2.4 2.4 0 0 0-.8 1.8 4.2 4.2 0 0 0 .9 2.2 9.6 9.6 0 0 0 3.7 3.3 12 12 0 0 0 1.3.5 3.1 3.1 0 0 0 1.4.1 2.6 2.6 0 0 0 1.7-1.2 2.1 2.1 0 0 0 .1-1.2c-.1 0-.2-.1-.4-.2z"/></svg>
</a>

<script src="/assets/js/app.js" defer></script>
</body>
</html>

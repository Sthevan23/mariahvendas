<?php
use App\Core\Helpers;
$store = Helpers::setting('store_name', 'Ateliê Mariah');
$flash = Helpers::flash();
$cartCount = Helpers::cartCount();
$favCount = count($_SESSION['favorites'] ?? []);
if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['_csrf'];
$whatsapp = Helpers::whatsappLink('Olá! Gostaria de atendimento da ' . $store . '.');
$logo = '/assets/brand/logo-header.png';
$logoFooter = '/assets/brand/logo-header.png';
$favicon = '/assets/brand/logo-favicon.png';
$logoType = 'image/png';
$instagramHandle = ltrim((string) Helpers::setting('instagram', ''), '@');
$instagramUrl = $instagramHandle !== '' ? 'https://instagram.com/' . rawurlencode($instagramHandle) : '#';
$tagline = (string) Helpers::setting('tagline', 'Vestidos de festa exclusivos — formatura, debutante e eventos');
$pageTitle = (string) ($title ?? $store);
$documentTitle = ($pageTitle !== '' && $pageTitle !== $store) ? ($pageTitle . ' | ' . $store) : $store;
if (!empty($seo_title)) {
    $documentTitle = (string) $seo_title;
}
$ogTitle = (string) ($og_title ?? $documentTitle);
$ogType = (string) ($og_type ?? 'website');
$currentPathForSeo = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$noindex = !empty($noindex) || (bool) preg_match('#^/(carrinho|checkout|conta|login|cadastro|favoritos|admin|pedido)(/|$)#', $currentPathForSeo);
$ogDescription = (string) ($meta_description ?? $tagline);
$ogImagePath = (string) ($og_image ?? Helpers::setting('og_image', '/assets/brand/og-share.jpg'));
$ogImageUrl = Helpers::absoluteUrl($ogImagePath);
$ogPageUrl = Helpers::absoluteUrl(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= Helpers::e($documentTitle) ?></title>
  <meta name="description" content="<?= Helpers::e($ogDescription) ?>">
  <link rel="canonical" href="<?= Helpers::e($ogPageUrl) ?>">
  <?php if ($noindex): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
  <meta property="og:type" content="<?= Helpers::e($ogType) ?>">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:site_name" content="<?= Helpers::e($store) ?>">
  <meta property="og:url" content="<?= Helpers::e($ogPageUrl) ?>">
  <meta property="og:title" content="<?= Helpers::e($ogTitle) ?>">
  <meta property="og:description" content="<?= Helpers::e($ogDescription) ?>">
  <meta property="og:image" content="<?= Helpers::e($ogImageUrl) ?>">
  <meta property="og:image:secure_url" content="<?= Helpers::e($ogImageUrl) ?>">
  <meta property="og:image:type" content="image/jpeg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="1200">
  <meta property="og:image:alt" content="<?= Helpers::e($store) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= Helpers::e($ogTitle) ?>">
  <meta name="twitter:description" content="<?= Helpers::e($ogDescription) ?>">
  <meta name="twitter:image" content="<?= Helpers::e($ogImageUrl) ?>">
  <link rel="icon" href="<?= Helpers::e($favicon) ?>" type="<?= Helpers::e($logoType) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css?v=24">
  <script type="application/ld+json">
  <?= json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'ClothingStore',
      'name' => $store,
      'url' => Helpers::appUrl(),
      'image' => Helpers::absoluteUrl('/assets/brand/logo-header.png'),
      'description' => Helpers::setting('tagline'),
      'telephone' => Helpers::setting('phone'),
      'email' => Helpers::setting('email'),
      'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Divinópolis', 'addressRegion' => 'MG', 'addressCountry' => 'BR'],
      'sameAs' => $instagramHandle !== '' ? [$instagramUrl] : [],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
  </script>
  <?php if (!empty($jsonld)): ?>
  <script type="application/ld+json">
  <?= json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
  </script>
  <?php endif; ?>
</head>
<body>
<?php
$navItems = [
  ['Vestidos', '/catalogo'],
  ['Coleção Festa', '/categoria/colecao-atelier'],
  ['Novidades', '/categoria/novidades'],
  ['Promoções', '/categoria/promocoes'],
  ['Trocas', '/trocas'],
];
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isCurrent = static fn(string $href): bool => $href === $currentPath || str_starts_with($currentPath, $href . '/');
$accountHref = \App\Core\Auth::check() ? '/conta' : '/login';
?>
<header class="site-header" data-header>
  <div class="container header-inner">
    <div class="header-brand">
      <button class="icon-btn menu-toggle" type="button" data-menu-toggle aria-label="Abrir menu" aria-expanded="false" aria-controls="mobile-nav">
        <svg viewBox="0 0 24 24"><path d="M4 8h16"/><path d="M4 16h16"/></svg>
      </button>
      <a class="logo logo-brand logo-brand--mark brand-lockup" href="/" aria-label="<?= Helpers::e($store) ?>">
        <img src="<?= Helpers::e($logo) ?>" alt="" width="56" height="56" decoding="async">
        <span class="brand-lockup-text"><?= Helpers::e($store) ?></span>
      </a>
    </div>
    <nav class="nav-desktop" aria-label="Principal">
      <?php foreach ($navItems as [$label, $href]): ?>
        <a href="<?= Helpers::e($href) ?>"<?= $isCurrent($href) ? ' aria-current="page"' : '' ?>><?= Helpers::e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="header-actions">
      <button class="icon-btn" type="button" data-search-toggle aria-label="Pesquisar">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
      </button>
      <a class="icon-btn" href="<?= Helpers::e($accountHref) ?>" aria-label="Minha conta">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
      </a>
      <a class="icon-btn" href="/favoritos" aria-label="Favoritos">
        <svg viewBox="0 0 24 24"><path d="M12 21s-7-4.5-9.5-8.5C.5 9 2.5 5 6.2 5c2 0 3.3 1.2 3.8 2 .5-.8 1.8-2 3.8-2 3.7 0 5.7 4 3.7 7.5C19 16.5 12 21 12 21z"/></svg>
        <?php if ($favCount): ?><span class="badge"><?= $favCount ?></span><?php endif; ?>
      </a>
      <a class="icon-btn" href="/carrinho" aria-label="Sacola" data-bag-icon>
        <svg viewBox="0 0 24 24"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg>
        <span class="badge" data-cart-count <?= $cartCount ? '' : 'hidden' ?>><?= $cartCount ?></span>
      </a>
    </div>
  </div>
  <div class="search-panel" data-search-panel>
    <form action="/busca" method="get">
      <input type="search" name="q" placeholder="Buscar vestidos..." aria-label="Buscar">
      <button class="btn" type="submit">Buscar</button>
    </form>
  </div>
</header>

<nav class="mobile-nav" id="mobile-nav" data-mobile-nav aria-label="Menu">
  <?php foreach ($navItems as [$label, $href]): ?>
    <a href="<?= Helpers::e($href) ?>"><?= Helpers::e($label) ?></a>
  <?php endforeach; ?>
  <div class="mobile-nav__footer">
    <a href="<?= Helpers::e($accountHref) ?>">Minha conta</a>
    <a href="<?= Helpers::e($whatsapp) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
  </div>
</nav>

<?php if ($flash): ?>
  <div class="flash <?= Helpers::e($flash['type']) ?>" data-flash><?= Helpers::e($flash['message']) ?></div>
<?php endif; ?>

<main><?= $content ?></main>

<footer class="site-footer">
  <div class="footer-cta">
    <div class="container footer-cta-inner">
      <div>
        <p class="footer-cta-eyebrow">Atendimento</p>
        <p class="footer-cta-title">Dúvidas de tamanho, prazo ou modelagem?</p>
      </div>
      <a class="btn footer-cta-btn" href="<?= Helpers::e($whatsapp) ?>" target="_blank" rel="noopener">Chamar no WhatsApp</a>
    </div>
  </div>
  <div class="footer-main">
    <div class="container footer-grid">
      <div class="footer-brand">
        <a class="logo logo-brand logo-brand--mark logo-brand--footer" href="/" aria-label="<?= Helpers::e($store) ?>">
          <img src="<?= Helpers::e($logoFooter) ?>" alt="Mariah Alta Costura" width="52" height="52" decoding="async">
        </a>
        <p class="footer-tagline"><?= Helpers::e(Helpers::setting('tagline')) ?></p>
        <p class="footer-address"><?= Helpers::e(Helpers::setting('address')) ?></p>
      </div>
      <div>
        <h4>Loja</h4>
        <a href="/catalogo">Todos os vestidos</a>
        <a href="/favoritos">Favoritos</a>
      </div>
      <div>
        <h4>Ajuda</h4>
        <a href="/trocas">Trocas</a>
        <a href="/privacidade">Privacidade</a>
      </div>
      <div>
        <h4>Contato</h4>
        <p><?= Helpers::e(Helpers::setting('phone')) ?></p>
        <p><a href="<?= Helpers::e($instagramUrl) ?>" target="_blank" rel="noopener"><?= Helpers::e(Helpers::setting('instagram')) ?></a></p>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="container footer-bottom-inner">
      <span>© <?= date('Y') ?> <?= Helpers::e($store) ?></span>
      <span>Vestidos de festa · Divinópolis/MG</span>
    </div>
  </div>
</footer>

<a class="wa-float" href="<?= Helpers::e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.7 14.9L2 22l5.3-1.4A10 10 0 1 0 12 2zm0 2a8 8 0 0 1 6.7 12.3l-.3.4.8 2.9-3-.8-.4.2A8 8 0 1 1 12 4zm4.2 9.6c-.2-.1-1.3-.6-1.5-.7s-.3-.1-.5.1-.6.7-.7.8-.3.2-.5.1a6.4 6.4 0 0 1-1.9-1.2 7.1 7.1 0 0 1-1.3-1.6c-.1-.2 0-.3.1-.4l.3-.4.1-.2a.5.5 0 0 0 0-.5c0-.1-.5-1.2-.7-1.6s-.4-.4-.5-.4h-.4a.8.8 0 0 0-.6.3 2.4 2.4 0 0 0-.8 1.8 4.2 4.2 0 0 0 .9 2.2 9.6 9.6 0 0 0 3.7 3.3 12 12 0 0 0 1.3.5 3.1 3.1 0 0 0 1.4.1 2.6 2.6 0 0 0 1.7-1.2 2.1 2.1 0 0 0 .1-1.2c-.1 0-.2-.1-.4-.2z"/></svg>
</a>

<script src="/assets/js/app.js?v=8" defer></script>
</body>
</html>

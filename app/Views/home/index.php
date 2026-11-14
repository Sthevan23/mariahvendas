<?php
use App\Core\Helpers;
$heroImage = Helpers::setting('hero_image');
$heroTitle = Helpers::setting('hero_title', 'Vestidos de festa para você brilhar');
$heroSub = Helpers::setting('hero_subtitle');
$store = Helpers::setting('store_name', 'Ateliê Mariah');
$instagramHandle = Helpers::setting('instagram', '@atelie_mariahaltacostura_');
$instagramUser = ltrim((string) $instagramHandle, '@');
$instagramUrl = $instagramUser !== '' ? 'https://instagram.com/' . rawurlencode($instagramUser) : '#';
$editorialImage = is_string($heroImage) && $heroImage !== '' ? $heroImage : '/uploads/moda/colecao/mariah-01.jpg';
$heroSrc = is_string($heroImage) && $heroImage !== '' ? $heroImage : '/uploads/moda/hero-atelier.jpg';
$heroWebp = preg_match('/\.jpe?g$/i', $heroSrc) ? preg_replace('/\.jpe?g$/i', '.webp', $heroSrc) : '';
$heroWebpOk = $heroWebp !== '' && is_file(dirname(__DIR__, 3) . '/public' . $heroWebp);
?>
<section class="hero">
  <div class="hero-media">
    <picture>
      <?php if ($heroWebpOk): ?>
      <source srcset="<?= Helpers::e($heroWebp) ?>" type="image/webp">
      <?php endif; ?>
      <img
        class="hero-media__img"
        src="<?= Helpers::e($heroSrc) ?>"
        width="2560"
        height="1440"
        alt=""
        fetchpriority="high"
        decoding="async">
    </picture>
  </div>
  <div class="hero-grain" aria-hidden="true"></div>
  <div class="hero-glow" aria-hidden="true"></div>
  <div class="container hero-content hero-enter">
    <p class="eyebrow eyebrow--light"><?= Helpers::e($store) ?></p>
    <h1><?= Helpers::e($heroTitle) ?></h1>
    <p class="hero-lead"><?= Helpers::e($heroSub) ?></p>
    <p class="hero-kicker">Divinópolis/MG · Atendimento pelo WhatsApp</p>
    <div class="hero-actions">
      <a class="btn btn-glow" href="/catalogo">Ver coleção</a>
      <a class="btn btn-outline" href="<?= Helpers::e(Helpers::whatsappLink('Olá! Gostaria de informações sobre vestidos de festa.')) ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
    </div>
  </div>
  <div class="hero-scroll" aria-hidden="true"><span></span></div>
</section>

<section class="section collections" aria-labelledby="colecoes-titulo">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <div>
        <p class="eyebrow">Explore</p>
        <h2 id="colecoes-titulo">Coleções</h2>
      </div>
    </div>
    <div class="collections-grid">
      <a class="collection-tile reveal" href="<?= !empty($storeEmpty) ? Helpers::e(Helpers::whatsappLink('Olá! Gostaria de conhecer a Coleção Festa.')) : '/categoria/colecao-atelier' ?>"<?= !empty($storeEmpty) ? ' target="_blank" rel="noopener"' : '' ?>>
        <img src="/uploads/moda/colecao/mariah-02.jpg" alt="" loading="lazy" width="800" height="1000">
        <span class="collection-tile__body">
          <small>Festa &amp; formatura</small>
          <strong>Coleção Festa</strong>
          <em><?= !empty($storeEmpty) ? 'Em breve · WhatsApp' : 'Ver peças' ?></em>
        </span>
      </a>
    </div>
  </div>
</section>

<?php if ($collection): ?>
<section class="section section--surface">
  <div class="container">
    <div class="section-head section-head--center reveal">
      <div>
        <p class="eyebrow">Coleção</p>
        <h2>Vestidos de festa</h2>
        <p class="section-lead muted">Peças exclusivas para formatura, debutante, aniversário e ocasiões especiais.</p>
      </div>
    </div>
    <div class="product-grid product-grid--premium">
      <?php foreach ($collection as $product): ?>
        <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
    <p class="section-cta reveal"><a class="btn btn-outline" href="/catalogo">Ver todos os vestidos</a></p>
  </div>
</section>
<?php endif; ?>

<section class="editorial reveal">
  <div class="editorial-media" style="background-image:url('<?= Helpers::e($editorialImage) ?>')"></div>
  <div class="editorial-copy">
    <p class="eyebrow">Ateliê</p>
    <h2>Alta costura para momentos inesquecíveis</h2>
    <p class="muted">Cada vestido é pensado com cuidado artesanal — modelagem, acabamento e brilho na medida certa para você se sentir única na sua festa.</p>
    <ul class="editorial-list">
      <li>Formatura &amp; colação</li>
      <li>Debutante &amp; aniversário</li>
      <li>Madrinhas &amp; eventos</li>
    </ul>
    <p class="editorial-actions"><a class="btn" href="/catalogo">Explorar vestidos</a></p>
  </div>
</section>

<?php if ($sale): ?>
<section class="section section--tint">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Ofertas</p>
        <h2>Promoções</h2>
        <p class="section-lead muted">Peças selecionadas com condições especiais.</p>
      </div>
      <a href="/categoria/promocoes">Ver ofertas</a>
    </div>
    <div class="product-grid">
      <?php foreach ($sale as $product): ?>
        <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container pillars">
    <div class="pillar reveal">
      <span class="pillar-icon" aria-hidden="true">✦</span>
      <h3>Modelagem exclusiva</h3>
      <p>Caimento pensado para valorizar sua silhueta em cada ocasião.</p>
    </div>
    <div class="pillar reveal">
      <span class="pillar-icon" aria-hidden="true">✦</span>
      <h3>Atendimento no WhatsApp</h3>
      <p>Tire dúvidas de tamanho, prazo e personalização com a equipe.</p>
    </div>
    <div class="pillar reveal">
      <span class="pillar-icon" aria-hidden="true">✦</span>
      <h3>Compra tranquila</h3>
      <p>Pedido registrado, checkout claro e suporte até a entrega.</p>
    </div>
  </div>
</section>

<?php if (!empty($reviews)): ?>
<section class="section section--surface" style="padding-top:0">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Clientes</p>
        <h2>Depoimentos</h2>
      </div>
    </div>
    <div class="testimonial-grid">
      <?php foreach ($reviews as $r): ?>
        <blockquote class="testimonial reveal">
          <p><?= Helpers::e($r['comment']) ?></p>
          <footer>— <?= Helpers::e($r['customer_name']) ?></footer>
        </blockquote>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($instagram): ?>
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Instagram</p>
        <h2><a class="insta-title" href="<?= Helpers::e($instagramUrl) ?>" target="_blank" rel="noopener"><?= Helpers::e($instagramHandle) ?></a></h2>
      </div>
      <a href="<?= Helpers::e($instagramUrl) ?>" target="_blank" rel="noopener">Seguir</a>
    </div>
    <div class="instagram-grid">
      <?php foreach ($instagram as $img): ?>
        <a class="instagram-tile reveal" href="<?= Helpers::e($instagramUrl) ?>" target="_blank" rel="noopener">
          <img src="<?= Helpers::e($img) ?>" alt="Look <?= Helpers::e($store) ?> no Instagram" loading="lazy">
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

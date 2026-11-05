<?php
use App\Core\Helpers;
$heroImage = Helpers::setting('hero_image');
$heroTitle = Helpers::setting('hero_title', 'Seu estilo, sua essência');
$heroSub = Helpers::setting('hero_subtitle');
?>
<section class="hero">
  <div class="hero-media" style="background-image:url('<?= Helpers::e($heroImage) ?>')"></div>
  <div class="hero-grain" aria-hidden="true"></div>
  <div class="container hero-content reveal">
    <p class="eyebrow eyebrow--light">Mariah Vendas</p>
    <h1><?= Helpers::e($heroTitle) ?></h1>
    <p class="hero-lead"><?= Helpers::e($heroSub) ?></p>
    <div class="hero-actions">
      <a class="btn btn-glow" href="/categoria/colecao-atelier">Ver vestidos de festa</a>
      <a class="btn btn-outline" href="/catalogo">Explorar catálogo</a>
    </div>
  </div>
  <div class="hero-scroll" aria-hidden="true"><span></span></div>
</section>

<section class="trust-strip">
  <div class="container trust-strip-inner">
    <div class="trust-item reveal"><strong>Vestidos de festa</strong><span>Formatura e eventos</span></div>
  </div>
</section>

<section class="section section--surface">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Seleção</p>
        <h2>Vestidos de festa em destaque</h2>
      </div>
      <a href="/categoria/colecao-atelier">Ver coleção</a>
    </div>
    <div class="product-grid">
      <?php foreach ($featured as $product): ?>
        <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Fresh</p>
        <h2>Lançamentos da semana</h2>
      </div>
      <a href="/categoria/novidades">Novidades</a>
    </div>
    <div class="product-grid">
      <?php foreach ($news as $product): ?>
        <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Favoritas</p>
        <h2>Mais vendidos</h2>
      </div>
    </div>
    <div class="product-grid">
      <?php foreach ($bestsellers as $product): ?>
        <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Explore</p>
        <h2>Categorias</h2>
      </div>
    </div>
    <div class="category-grid">
      <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
        <a class="category-card reveal" href="/categoria/<?= Helpers::e($cat['slug']) ?>">
          <img src="<?= Helpers::e($cat['image']) ?>" alt="<?= Helpers::e($cat['name']) ?>" loading="lazy">
          <span><?= Helpers::e($cat['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="editorial reveal">
  <div class="editorial-media" style="background-image:url('/uploads/moda/editorial-atelier.jpg')"></div>
  <div class="editorial-copy">
    <p class="eyebrow">Editorial</p>
    <h2>Festa · Brilho & elegância</h2>
    <p class="muted">Vestidos pensados para formatura, debutante, aniversário, madrinhas e festas — brilho e elegância em cada detalhe.</p>
    <p style="margin-top:1.5rem"><a class="btn" href="/categoria/colecao-atelier">Ver Coleção Festa</a></p>
  </div>
</section>

<?php if ($sale): ?>
<section class="section">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Ofertas</p>
        <h2>Promoções</h2>
      </div>
      <a href="/categoria/promocoes">Todas as ofertas</a>
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
  <div class="container benefits">
    <div class="benefit reveal">
      <h3>Compra segura</h3>
      <p>Pedidos registrados com acompanhamento e atendimento humano.</p>
    </div>
    <div class="benefit reveal">
      <h3>Atendimento personalizado</h3>
      <p>Tire dúvidas de tamanho e modelagem pelo WhatsApp.</p>
    </div>
    <div class="benefit reveal">
      <h3>Facilidade para comprar</h3>
      <p>Checkout claro, cupons e entrega para todo o Brasil.</p>
    </div>
  </div>
</section>

<?php if (!empty($reviews)): ?>
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Clientes</p>
        <h2>Depoimentos</h2>
      </div>
    </div>
    <div class="product-grid">
      <?php foreach ($reviews as $r): ?>
        <blockquote class="admin-card reveal">
          <strong><?= Helpers::e($r['customer_name']) ?></strong>
          <p><?= Helpers::e($r['comment']) ?></p>
        </blockquote>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-head reveal">
      <div>
        <p class="eyebrow">Social</p>
        <h2><?= Helpers::e(Helpers::setting('instagram', '@atelie_mariahaltacostura_')) ?></h2>
      </div>
    </div>
    <div class="instagram-grid">
      <?php foreach ($instagram as $img): ?>
        <img class="reveal" src="<?= Helpers::e($img) ?>" alt="Look Mariah Vendas no Instagram" loading="lazy">
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="newsletter">
  <div class="container newsletter-inner reveal">
    <div>
      <p class="eyebrow" style="color:#c4a484">Newsletter</p>
      <h2>Receba novidades e pré-vendas</h2>
    </div>
    <form method="post" action="/newsletter">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <input type="email" name="email" required placeholder="Seu melhor e-mail">
      <button class="btn" type="submit">Quero receber</button>
    </form>
  </div>
</section>

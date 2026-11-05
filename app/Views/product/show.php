<?php
use App\Core\Helpers;
$main = $images[0]['path'] ?? null;
?>
<div class="container">
  <div class="breadcrumbs">
    <a href="/">Início</a> /
    <?php if (!empty($product['category_slug'])): ?>
      <a href="/categoria/<?= Helpers::e($product['category_slug']) ?>"><?= Helpers::e($product['category_name']) ?></a> /
    <?php endif; ?>
    <?= Helpers::e($product['name']) ?>
  </div>

  <div class="product-detail">
    <div>
      <div class="gallery-main" data-gallery-main>
        <?php if ($main): ?><img src="<?= Helpers::e($main) ?>" alt="<?= Helpers::e($product['name']) ?>"><?php endif; ?>
      </div>
      <?php if (count($images) > 1): ?>
        <div class="gallery-thumbs">
          <?php foreach ($images as $i => $img): ?>
            <button type="button" data-thumb data-src="<?= Helpers::e($img['path']) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
              <img src="<?= Helpers::e($img['path']) ?>" alt="">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <p class="eyebrow"><?= Helpers::e($product['category_name'] ?? Helpers::setting('store_name', 'Ateliê Mariah')) ?></p>
      <h1 class="page-title"><?= Helpers::e($product['name']) ?></h1>
      <div class="price" style="margin:1rem 0 1.25rem;font-size:1.2rem">
        <strong><?= Helpers::money($product['price']) ?></strong>
        <?php if (!empty($product['compare_at_price']) && $product['compare_at_price'] > $product['price']): ?>
          <s><?= Helpers::money($product['compare_at_price']) ?></s>
        <?php endif; ?>
      </div>
      <p class="muted"><?= nl2br(Helpers::e($product['description'])) ?></p>

      <?php
        $defaultSize = $defaultVariant['size'] ?? ($sizes[0] ?? null);
        $defaultColor = $defaultVariant['color'] ?? (array_key_first($colors) ?: null);
        $defaultStock = (int) ($defaultVariant['stock'] ?? 0);
        $defaultId = (int) ($defaultVariant['id'] ?? 0);
        $canBuy = $defaultId > 0 && $defaultStock > 0;
      ?>
      <form method="post" action="/carrinho/adicionar" data-ajax-cart>
        <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
        <input type="hidden" name="variant_id" value="<?= $canBuy ? $defaultId : '' ?>" data-variant-id>
        <input type="hidden" name="buy_now" value="0" data-buy-now>
        <p style="margin:1.5rem 0 .4rem;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase">Tamanho</p>
        <div class="sizes">
          <?php foreach ($sizes as $size): ?>
            <button type="button" class="size-opt <?= $size === $defaultSize ? 'active' : '' ?>" data-size="<?= Helpers::e($size) ?>"><?= Helpers::e($size) ?></button>
          <?php endforeach; ?>
        </div>
        <p style="margin:1rem 0 .4rem;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase">Cor</p>
        <div class="swatches">
          <?php foreach ($colors as $color => $hex): ?>
            <button type="button" class="swatch <?= $color === $defaultColor ? 'active' : '' ?>" data-color="<?= Helpers::e($color) ?>" title="<?= Helpers::e($color) ?>" aria-label="<?= Helpers::e($color) ?>" style="background:<?= Helpers::e($hex ?: '#ddd') ?>"></button>
          <?php endforeach; ?>
        </div>
        <div class="qty-row">
          <label for="qty">Qtd</label>
          <input id="qty" type="number" name="qty" min="1" value="1">
          <span class="muted" data-stock-label><?= $canBuy ? 'Disponível (' . $defaultStock . ')' : 'Indisponível' ?></span>
        </div>
        <div class="detail-actions">
          <button class="btn btn-block" type="submit" data-add-btn data-action="add" <?= $canBuy ? '' : 'disabled' ?>>Adicionar à sacola</button>
          <button class="btn btn-outline btn-block" type="submit" data-add-btn data-action="buy" <?= $canBuy ? '' : 'disabled' ?>>Comprar agora</button>
          <a class="btn btn-ghost btn-block" href="<?= Helpers::e(Helpers::whatsappLink('Olá! Quero consultar sobre: ' . $product['name'])) ?>" target="_blank" rel="noopener">Consultar no WhatsApp</a>
        </div>
      </form>

      <div class="info-box">
        <p><strong>Guia de medidas:</strong> P (36), M (38), G (40), GG (42). Em caso de dúvida, fale conosco.</p>
        <p><strong>Trocas e entrega:</strong> <?= Helpers::e(Helpers::setting('delivery_info')) ?> <?= Helpers::e(Helpers::setting('exchange_policy')) ?></p>
      </div>
    </div>
  </div>

  <?php if ($related): ?>
    <div class="section-head">
      <h2>Você também pode gostar</h2>
    </div>
    <div class="product-grid" style="padding-bottom:3rem">
      <?php foreach ($related as $product): ?>
        <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script>
window.MANDALLA_VARIANTS = <?= json_encode(array_map(fn($v) => [
    'id' => (int)$v['id'],
    'size' => $v['size'],
    'color' => $v['color'],
    'stock' => (int)$v['stock'],
], $variants), JSON_UNESCAPED_UNICODE) ?>;
</script>

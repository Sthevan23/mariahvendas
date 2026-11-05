<?php
use App\Core\Helpers;
$p = $product;
$productUrl = '/produto/' . ($p['slug'] ?? '');
?>
<article class="product-card reveal">
  <a class="product-card-hit" href="<?= Helpers::e($productUrl) ?>" aria-label="<?= Helpers::e('Ver ' . ($p['name'] ?? 'peça')) ?>">
    <span class="media">
      <span class="media-shade" aria-hidden="true"></span>
      <span class="media-cta">Ver peça</span>
      <?php if (!empty($p['image'])): ?>
        <img src="<?= Helpers::e($p['image']) ?>" alt="<?= Helpers::e($p['image_alt'] ?? $p['name']) ?>" loading="lazy">
      <?php endif; ?>
      <span class="tags">
        <?php if (!empty($p['is_new'])): ?><span class="tag">Novo</span><?php endif; ?>
        <?php if (!empty($p['is_sale'])): ?><span class="tag">Promoção</span><?php endif; ?>
        <?php if (!empty($p['is_bestseller'])): ?><span class="tag">Mais vendido</span><?php endif; ?>
      </span>
    </span>
    <span class="meta">
      <span class="product-card-title"><?= Helpers::e($p['name']) ?></span>
      <span class="price">
        <span><?= Helpers::money($p['price']) ?></span>
        <?php if (!empty($p['compare_at_price']) && $p['compare_at_price'] > $p['price']): ?>
          <s><?= Helpers::money($p['compare_at_price']) ?></s>
        <?php endif; ?>
      </span>
    </span>
  </a>
  <form class="fav-form" method="post" action="/favoritos/toggle">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
    <button class="icon-btn" type="submit" aria-label="Favoritar" title="Favoritar">
      <svg viewBox="0 0 24 24"><path d="M12 21s-7-4.5-9.5-8.5C.5 9 2.5 5 6.2 5c2 0 3.3 1.2 3.8 2 .5-.8 1.8-2 3.8-2 3.7 0 5.7 4 3.7 7.5C19 16.5 12 21 12 21z" <?= Helpers::isFavorite((int)$p['id']) ? 'fill="currentColor" stroke="none"' : '' ?>/></svg>
    </button>
  </form>
</article>

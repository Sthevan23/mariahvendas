<?php use App\Core\Helpers; ?>
<div class="container" style="padding:2rem 0 4rem">
  <h1 class="page-title">Favoritos</h1>
  <?php if (!$products): ?>
    <div class="empty-state">
      <h2>Nenhum favorito ainda</h2>
      <p>Salve peças para comparar e comprar depois.</p>
      <p><a class="btn" href="/catalogo">Explorar</a></p>
    </div>
  <?php else: ?>
    <div class="product-grid" style="margin-top:2rem">
      <?php foreach ($products as $product): ?>
        <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

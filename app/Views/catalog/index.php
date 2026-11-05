<?php use App\Core\Helpers; ?>
<div class="container catalog-page">
  <div class="breadcrumbs"><a href="/">Início</a> / <?= Helpers::e($title) ?></div>
  <div class="section-head catalog-intro">
    <div>
      <p class="eyebrow">Vestidos</p>
      <h1 class="page-title"><?= Helpers::e($title) ?></h1>
    </div>
    <p class="muted"><?= (int)($total ?? count($products)) ?> vestidos</p>
  </div>

  <div class="catalog-layout">
    <form class="filters" method="get" action="<?= !empty($category['slug']) && empty($filters['search']) && empty($filters['is_new']) && (empty($filters['is_sale']) || ($filters['category_slug'] ?? '') === 'colecao-atelier') ? '/categoria/' . Helpers::e($category['slug']) : '/catalogo' ?>">
      <?php if (!empty($filters['search'])): ?>
        <input type="hidden" name="q" value="<?= Helpers::e($filters['search']) ?>">
      <?php endif; ?>
      <label>Tamanho
        <select name="tamanho">
          <option value="">Todos</option>
          <?php foreach ($sizes as $s): ?>
            <option value="<?= Helpers::e($s) ?>" <?= ($filters['size'] ?? '') === $s ? 'selected' : '' ?>><?= Helpers::e($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Cor
        <select name="cor">
          <option value="">Todas</option>
          <?php foreach ($colors as $c): ?>
            <option value="<?= Helpers::e($c['color']) ?>" <?= ($filters['color'] ?? '') === $c['color'] ? 'selected' : '' ?>><?= Helpers::e($c['color']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Preço mín.
        <input type="number" step="0.01" name="min" value="<?= Helpers::e((string)($filters['min_price'] ?? '')) ?>">
      </label>
      <label>Preço máx.
        <input type="number" step="0.01" name="max" value="<?= Helpers::e((string)($filters['max_price'] ?? '')) ?>">
      </label>
      <label>Ordenar
        <select name="ordenar">
          <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Lançamentos</option>
          <option value="name" <?= ($filters['sort'] ?? '') === 'name' ? 'selected' : '' ?>>Nome</option>
          <option value="price_asc" <?= ($filters['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>Menor preço</option>
          <option value="price_desc" <?= ($filters['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Maior preço</option>
          <option value="bestsellers" <?= ($filters['sort'] ?? '') === 'bestsellers' ? 'selected' : '' ?>>Mais vendidos</option>
        </select>
      </label>
      <label style="display:flex;align-items:center;gap:.5rem">
        <input type="checkbox" name="disponivel" value="1" <?= !empty($filters['in_stock']) ? 'checked' : '' ?>> Somente disponíveis
      </label>
      <button class="btn btn-block" type="submit">Filtrar</button>
    </form>

    <div>
      <?php if (!$products): ?>
        <div class="empty-state">
          <h2>Nenhuma peça encontrada</h2>
          <p>Ajuste os filtros ou explore a Coleção Festa.</p>
          <p><a class="btn" href="/categoria/colecao-atelier">Ver Coleção Festa</a></p>
        </div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $product): ?>
            <?php require dirname(__DIR__) . '/partials/product-card.php'; ?>
          <?php endforeach; ?>
        </div>

        <?php if (($totalPages ?? 1) > 1): ?>
          <nav class="pagination" style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap;margin:2.5rem 0 1rem">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
              <?php
                $q = $query ?? [];
                $q['page'] = $p;
                $href = '?' . http_build_query($q);
              ?>
              <a class="btn <?= $p === ($page ?? 1) ? '' : 'btn-outline' ?>" style="min-height:40px;min-width:40px;padding:0 .75rem" href="<?= Helpers::e($href) ?>"><?= $p ?></a>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

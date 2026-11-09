<?php use App\Core\Helpers; $csrf = Helpers::e($_SESSION['_csrf'] ?? ''); $onSale = array_filter($products, fn($p) => (int)$p['is_sale'] === 1); ?>
<h1 class="page-title">Promoções</h1>
<p class="muted">O preço atual vira o "preço de" (riscado) e o desconto entra como preço de venda. Para voltar ao normal, use "Encerrar promoções".</p>

<form method="post" action="/admin/promocoes/aplicar" class="admin-card form-grid" style="margin-top:1rem">
  <input type="hidden" name="_csrf" value="<?= $csrf ?>">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Aplicar desconto</h3>
  <div class="form-grid two">
    <div class="form-group"><label for="promo-percent">Desconto (%)</label><input class="form-control" id="promo-percent" name="percent" inputmode="decimal" required placeholder="Ex.: 20"></div>
    <div class="form-group"><label for="promo-scope">Aplicar em</label>
      <select class="form-control" id="promo-scope" name="scope">
        <option value="selected">Produtos marcados abaixo</option>
        <option value="category">Uma categoria inteira</option>
        <option value="all">Todo o catálogo</option>
      </select>
    </div>
  </div>
  <div class="form-group"><label for="promo-category">Categoria (se escolher "Uma categoria inteira")</label>
    <select class="form-control" id="promo-category" name="category_id">
      <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= Helpers::e($c['name']) ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th><span class="sr-only">Selecionar</span></th><th>Produto</th><th>Preço atual</th><th>Preço "de"</th><th>Promoção</th></tr></thead>
      <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><input type="checkbox" name="product_ids[]" value="<?= (int)$p['id'] ?>" aria-label="Selecionar <?= Helpers::e($p['name']) ?>"></td>
          <td><?= Helpers::e($p['name']) ?><br><span class="muted"><?= Helpers::e($p['category_name'] ?? '-') ?></span></td>
          <td><?= Helpers::money($p['price']) ?></td>
          <td><?= $p['compare_at_price'] ? Helpers::money($p['compare_at_price']) : '-' ?></td>
          <td><?= (int)$p['is_sale'] ? 'Sim' : 'Não' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <button class="btn" type="submit" onclick="return confirm('Aplicar o desconto agora?')">Aplicar promoção</button>
</form>

<div class="admin-card" style="margin-top:1rem">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Promoções ativas (<?= count($onSale) ?>)</h3>
  <?php if ($onSale): ?>
    <p class="muted">Ao encerrar, todos esses produtos voltam ao preço "de" e saem da seção de promoção.</p>
    <form method="post" action="/admin/promocoes/encerrar" onsubmit="return confirm('Encerrar todas as promoções?')">
      <input type="hidden" name="_csrf" value="<?= $csrf ?>">
      <button class="btn btn-outline" type="submit">Encerrar promoções</button>
    </form>
  <?php else: ?>
    <p class="muted">Nenhuma promoção ativa no momento.</p>
  <?php endif; ?>
</div>

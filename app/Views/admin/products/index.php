<?php use App\Core\Helpers; ?>
<div class="section-head">
  <h1 class="page-title">Produtos</h1>
  <a class="btn" href="/admin/produtos/novo">Novo produto</a>
</div>
<form method="get" style="margin:1rem 0;display:flex;gap:.5rem">
  <input class="form-control" name="q" placeholder="Buscar..." value="<?= Helpers::e($_GET['q'] ?? '') ?>">
  <button class="btn btn-outline" type="submit">Buscar</button>
</form>
<table class="admin-table">
  <thead><tr><th>Produto</th><th>Preço</th><th>Estoque</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td>
        <div class="cart-item">
          <?php if (!empty($p['image'])): ?><img src="<?= Helpers::e($p['image']) ?>" alt=""><?php endif; ?>
          <div>
            <strong><?= Helpers::e($p['name']) ?></strong><br>
            <span class="muted"><?= Helpers::e($p['category_name'] ?? '-') ?></span>
          </div>
        </div>
      </td>
      <td><?= Helpers::money($p['price']) ?></td>
      <td><?= (int)($p['stock_total'] ?? 0) ?></td>
      <td><?= (int)$p['is_active'] ? 'Ativo' : 'Inativo' ?></td>
      <td><a href="/admin/produtos/<?= (int)$p['id'] ?>/editar">Editar</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

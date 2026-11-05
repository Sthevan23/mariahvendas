<?php use App\Core\Helpers; ?>
<h1 class="page-title">Estoque</h1>
<div class="admin-card">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Ajuste manual</h3>
  <form method="post" action="/admin/estoque" class="form-grid two">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <div class="form-group"><label>Variação</label>
      <select class="form-control" name="variant_id" required>
        <?php foreach ($rows as $r): ?>
          <option value="<?= (int)$r['id'] ?>"><?= Helpers::e($r['product_name'].' '.$r['size'].'/'.$r['color'].' ('.$r['stock'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Tipo</label>
      <select class="form-control" name="type"><option value="in">Entrada</option><option value="out">Saída</option></select>
    </div>
    <div class="form-group"><label>Quantidade</label><input class="form-control" type="number" min="1" name="quantity" required></div>
    <div class="form-group"><label>Nota</label><input class="form-control" name="note" value="Reposição manual"></div>
    <button class="btn" type="submit">Registrar</button>
  </form>
</div>
<table class="admin-table">
  <thead><tr><th>Produto</th><th>Tam</th><th>Cor</th><th>SKU</th><th>Estoque</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= Helpers::e($r['product_name']) ?></td>
      <td><?= Helpers::e($r['size']) ?></td>
      <td><?= Helpers::e($r['color']) ?></td>
      <td><?= Helpers::e($r['sku']) ?></td>
      <td style="<?= (int)$r['stock'] <= 5 ? 'color:#7a2e2e;font-weight:600' : '' ?>"><?= (int)$r['stock'] ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div class="admin-card">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Últimas movimentações</h3>
  <?php foreach ($movements as $m): ?>
    <p class="muted"><?= Helpers::e(date('d/m/Y H:i', strtotime($m['created_at']))) ?> — <?= Helpers::e($m['type']) ?> <?= (int)$m['quantity'] ?> · <?= Helpers::e($m['product_name']) ?> <?= Helpers::e($m['size']) ?>/<?= Helpers::e($m['color']) ?> · <?= Helpers::e($m['note']) ?></p>
  <?php endforeach; ?>
</div>

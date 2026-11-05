<?php use App\Core\Helpers; ?>
<a href="/admin/pedidos">← Pedidos</a>
<h1 class="page-title" style="margin-top:1rem"><?= Helpers::e($order['number']) ?></h1>
<div class="admin-card">
  <p><strong><?= Helpers::e($order['customer_name']) ?></strong> · <?= Helpers::e($order['customer_phone']) ?> · <?= Helpers::e($order['customer_email'] ?? '-') ?></p>
  <p><?= Helpers::e($order['shipping_address']) ?> <?= Helpers::e($order['shipping_city']) ?> <?= Helpers::e($order['shipping_state']) ?> <?= Helpers::e($order['shipping_zip']) ?></p>
  <p>Entrega: <?= Helpers::e($order['shipping_name'] ?? '-') ?> · Pagamento: <?= Helpers::e($order['payment_name'] ?? '-') ?></p>
  <?php foreach ($items as $item): ?>
    <p><?= (int)$item['quantity'] ?>x <?= Helpers::e($item['product_name']) ?> (<?= Helpers::e($item['size']) ?>/<?= Helpers::e($item['color']) ?>) — <?= Helpers::money($item['total']) ?></p>
  <?php endforeach; ?>
  <p><strong>Total: <?= Helpers::money($order['total']) ?></strong></p>
</div>
<form method="post" action="/admin/pedidos/<?= (int)$order['id'] ?>/status" class="admin-card form-grid">
  <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
  <div class="form-group">
    <label>Atualizar status</label>
    <select class="form-control" name="status">
      <?php foreach (['awaiting_confirmation','awaiting_payment','payment_approved','separating','shipped','delivered','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Nota</label><input class="form-control" name="note"></div>
  <button class="btn" type="submit">Salvar status</button>
</form>
<div class="admin-card">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Histórico</h3>
  <?php foreach ($history as $h): ?>
    <p class="muted"><?= Helpers::e(date('d/m/Y H:i', strtotime($h['created_at']))) ?> — <?= Helpers::e($h['status']) ?><?= $h['note'] ? ': '.Helpers::e($h['note']) : '' ?></p>
  <?php endforeach; ?>
</div>

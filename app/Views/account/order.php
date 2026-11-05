<?php use App\Core\Helpers; ?>
<div class="container" style="padding:2rem 0 4rem;max-width:800px">
  <a href="/conta">← Voltar</a>
  <h1 class="page-title" style="margin-top:1rem">Pedido <?= Helpers::e($order['number']) ?></h1>
  <p class="muted">Status: <?= Helpers::e($order['status']) ?> · Pagamento: <?= Helpers::e($order['payment_status']) ?></p>
  <div class="checkout-card">
    <?php foreach ($items as $item): ?>
      <p><?= (int)$item['quantity'] ?>x <?= Helpers::e($item['product_name']) ?> (<?= Helpers::e($item['size']) ?>/<?= Helpers::e($item['color']) ?>) — <?= Helpers::money($item['total']) ?></p>
    <?php endforeach; ?>
    <p><strong>Total: <?= Helpers::money($order['total']) ?></strong></p>
  </div>
  <h2 style="font-family:var(--serif);font-weight:500">Histórico</h2>
  <?php foreach ($history as $h): ?>
    <p class="muted"><?= Helpers::e(date('d/m/Y H:i', strtotime($h['created_at']))) ?> — <?= Helpers::e($h['status']) ?><?= $h['note'] ? ': ' . Helpers::e($h['note']) : '' ?></p>
  <?php endforeach; ?>
</div>

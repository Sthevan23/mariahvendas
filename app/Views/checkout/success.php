<?php use App\Core\Helpers; ?>
<div class="container" style="padding:3rem 0 5rem;max-width:760px">
  <p class="eyebrow">Pedido registrado</p>
  <h1 class="page-title">Obrigada, <?= Helpers::e($order['customer_name']) ?></h1>
  <p class="muted">Seu pedido <strong><?= Helpers::e($order['number']) ?></strong> foi criado e aguarda confirmação de pagamento.</p>

  <div class="checkout-card" style="margin-top:2rem">
    <?php foreach ($items as $item): ?>
      <p><?= (int)$item['quantity'] ?>x <?= Helpers::e($item['product_name']) ?> — <?= Helpers::e($item['size']) ?> / <?= Helpers::e($item['color']) ?> — <?= Helpers::money($item['total']) ?></p>
    <?php endforeach; ?>
    <hr style="border:0;border-top:1px solid var(--line);margin:1rem 0">
    <p>Total: <strong><?= Helpers::money($order['total']) ?></strong></p>
    <p class="muted">Pagamento: <?= Helpers::e($order['payment_name'] ?? '') ?> · Entrega: <?= Helpers::e($order['shipping_name'] ?? '') ?></p>
    <p style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem">
      <a class="btn" href="<?= Helpers::e($whatsapp) ?>" target="_blank" rel="noopener">Enviar pedido no WhatsApp</a>
      <a class="btn btn-outline" href="/catalogo">Continuar comprando</a>
    </p>
  </div>
</div>

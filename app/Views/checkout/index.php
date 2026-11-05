<?php use App\Core\Helpers; ?>
<div class="container" style="padding:2rem 0 4rem">
  <h1 class="page-title">Checkout</h1>
  <div class="catalog-layout" style="margin-top:2rem">
    <form method="post" action="/checkout" class="checkout-card form-grid">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <div class="form-group">
        <label>Nome completo</label>
        <input class="form-control" name="customer_name" required value="<?= Helpers::e($user['name'] ?? '') ?>">
      </div>
      <div class="form-grid two">
        <div class="form-group">
          <label>WhatsApp / telefone</label>
          <input class="form-control" name="customer_phone" required>
        </div>
        <div class="form-group">
          <label>E-mail</label>
          <input class="form-control" type="email" name="customer_email" value="<?= Helpers::e($user['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Endereço</label>
        <input class="form-control" name="shipping_address">
      </div>
      <div class="form-grid two">
        <div class="form-group"><label>Cidade</label><input class="form-control" name="shipping_city"></div>
        <div class="form-group"><label>UF</label><input class="form-control" name="shipping_state" maxlength="2"></div>
      </div>
      <div class="form-group"><label>CEP</label><input class="form-control" name="shipping_zip"></div>

      <div class="form-group">
        <label>Forma de entrega</label>
        <?php foreach ($shippingMethods as $s): ?>
          <label style="display:flex;gap:.6rem;align-items:center;margin:.4rem 0;color:var(--ink)">
            <input type="radio" name="shipping_method_id" value="<?= (int)$s['id'] ?>" required>
            <?= Helpers::e($s['name']) ?> — <?= Helpers::money($s['price']) ?> (<?= Helpers::e($s['estimated_days']) ?>)
          </label>
        <?php endforeach; ?>
      </div>

      <div class="form-group">
        <label>Forma de pagamento</label>
        <?php foreach ($paymentMethods as $p): ?>
          <label style="display:flex;gap:.6rem;align-items:flex-start;margin:.4rem 0;color:var(--ink)">
            <input type="radio" name="payment_method_id" value="<?= (int)$p['id'] ?>" required>
            <span><strong><?= Helpers::e($p['name']) ?></strong><br><span class="muted"><?= Helpers::e($p['instructions']) ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="form-group">
        <label>Observações</label>
        <textarea class="form-control" name="notes" rows="3"></textarea>
      </div>
      <button class="btn btn-block" type="submit">Confirmar pedido</button>
    </form>

    <aside class="cart-summary">
      <h2 style="font-family:var(--serif);font-weight:500;margin-top:0">Resumo</h2>
      <?php foreach ($items as $item): ?>
        <p><?= (int)$item['qty'] ?>x <?= Helpers::e($item['name']) ?> (<?= Helpers::e($item['size']) ?>/<?= Helpers::e($item['color']) ?>) — <?= Helpers::money($item['price'] * $item['qty']) ?></p>
      <?php endforeach; ?>
      <hr style="border:0;border-top:1px solid var(--line);margin:1rem 0">
      <p>Subtotal: <?= Helpers::money($totals['subtotal']) ?></p>
      <?php if ($totals['discount'] > 0): ?><p>Desconto: -<?= Helpers::money($totals['discount']) ?></p><?php endif; ?>
      <p class="muted">O frete será somado conforme a modalidade escolhida.</p>
    </aside>
  </div>
</div>

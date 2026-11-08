<?php use App\Core\Helpers; ?>
<div class="container" style="padding:2rem 0 4rem">
  <h1 class="page-title">Checkout</h1>
  <div class="checkout-layout">
    <form method="post" action="/checkout" class="checkout-card form-grid">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <div class="form-group">
        <label for="checkout-customer-name">Nome completo</label>
        <input class="form-control" id="checkout-customer-name" name="customer_name" autocomplete="name" required value="<?= Helpers::e($user['name'] ?? '') ?>">
      </div>
      <div class="form-grid two">
        <div class="form-group">
          <label for="checkout-customer-phone">WhatsApp / telefone</label>
          <input class="form-control" id="checkout-customer-phone" name="customer_phone" type="tel" autocomplete="tel" required>
        </div>
        <div class="form-group">
          <label for="checkout-customer-email">E-mail</label>
          <input class="form-control" id="checkout-customer-email" type="email" name="customer_email" autocomplete="email" value="<?= Helpers::e($user['email'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="checkout-shipping-address">Endereço</label>
        <input class="form-control" id="checkout-shipping-address" name="shipping_address" autocomplete="street-address">
      </div>
      <div class="form-grid two">
        <div class="form-group"><label for="checkout-shipping-city">Cidade</label><input class="form-control" id="checkout-shipping-city" name="shipping_city" autocomplete="address-level2"></div>
        <div class="form-group"><label for="checkout-shipping-state">UF</label><input class="form-control" id="checkout-shipping-state" name="shipping_state" maxlength="2" autocomplete="address-level1"></div>
      </div>
      <div class="form-group"><label for="checkout-shipping-zip">CEP</label><input class="form-control" id="checkout-shipping-zip" name="shipping_zip" inputmode="numeric" autocomplete="postal-code"></div>

      <?php if ($shippingEnabled): ?>
      <div class="form-group" role="radiogroup" aria-labelledby="checkout-shipping-legend">
        <span class="form-label" id="checkout-shipping-legend">Forma de entrega</span>
        <?php foreach ($shippingMethods as $s): ?>
          <label class="option">
            <input type="radio" name="shipping_method_id" value="<?= (int)$s['id'] ?>" required>
            <?= Helpers::e($s['name']) ?> — <?= Helpers::money($s['price']) ?> (<?= Helpers::e($s['estimated_days']) ?>)
          </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="form-group" role="radiogroup" aria-labelledby="checkout-payment-legend">
        <span class="form-label" id="checkout-payment-legend">Forma de pagamento</span>
        <?php foreach ($paymentMethods as $p): ?>
          <label class="option">
            <input type="radio" name="payment_method_id" value="<?= (int)$p['id'] ?>" required>
            <span><strong><?= Helpers::e($p['name']) ?></strong><br><span class="muted"><?= Helpers::e($p['instructions']) ?></span></span>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="form-group">
        <label for="checkout-notes">Observações</label>
        <textarea class="form-control" id="checkout-notes" name="notes" rows="3"></textarea>
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
      <?php if ($shippingEnabled): ?>
        <p class="muted">O frete será somado conforme a modalidade escolhida.</p>
      <?php endif; ?>
    </aside>
  </div>
</div>

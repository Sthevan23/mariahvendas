<?php use App\Core\Helpers; ?>
<div class="container" style="padding:2rem 0 4rem">
  <h1 class="page-title">Sacola</h1>
  <?php if (!$items): ?>
    <div class="empty-state">
      <h2>Sua sacola está vazia</h2>
      <p>Explore a coleção e encontre o look perfeito.</p>
      <p><a class="btn" href="/catalogo">Continuar comprando</a></p>
    </div>
  <?php else: ?>
    <table class="cart-table">
      <thead><tr><th>Produto</th><th>Qtd</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td>
            <div class="cart-item">
              <?php if ($item['image']): ?><img src="<?= Helpers::e($item['image']) ?>" alt=""><?php endif; ?>
              <div>
                <strong><?= Helpers::e($item['name']) ?></strong><br>
                <span class="muted"><?= Helpers::e($item['size']) ?> / <?= Helpers::e($item['color']) ?></span><br>
                <span><?= Helpers::money($item['price']) ?></span>
              </div>
            </div>
          </td>
          <td>
            <form method="post" action="/carrinho/atualizar" style="display:flex;gap:.4rem;align-items:center">
              <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
              <input type="hidden" name="variant_id" value="<?= (int)$item['variant_id'] ?>">
              <input class="form-control" style="width:70px" type="number" min="0" name="qty" value="<?= (int)$item['qty'] ?>" onchange="this.form.submit()">
              <button class="btn btn-outline" type="submit" style="min-height:40px;padding:0 .8rem">Atualizar</button>
            </form>
          </td>
          <td><?= Helpers::money($item['price'] * $item['qty']) ?></td>
          <td>
            <form method="post" action="/carrinho/remover">
              <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
              <input type="hidden" name="variant_id" value="<?= (int)$item['variant_id'] ?>">
              <button class="btn btn-ghost" type="submit">Remover</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <div class="cart-summary">
      <form method="post" action="/carrinho/cupom" style="display:flex;gap:.5rem;margin-bottom:1rem">
        <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
        <input class="form-control" type="text" name="code" placeholder="Cupom" value="<?= Helpers::e($totals['coupon']['code'] ?? '') ?>">
        <button class="btn btn-outline" type="submit">Aplicar</button>
      </form>
      <p>Subtotal: <strong><?= Helpers::money($totals['subtotal']) ?></strong></p>
      <?php if ($totals['discount'] > 0): ?><p>Desconto: <strong>-<?= Helpers::money($totals['discount']) ?></strong></p><?php endif; ?>
      <p style="font-size:1.2rem">Total parcial: <strong><?= Helpers::money($totals['subtotal'] - $totals['discount']) ?></strong></p>
      <p style="margin-top:1.25rem"><a class="btn btn-block" href="/checkout">Finalizar pedido</a></p>
    </div>
  <?php endif; ?>
</div>

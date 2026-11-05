<?php use App\Core\Helpers; ?>
<div class="container" style="padding:2rem 0 4rem">
  <div class="section-head">
    <div>
      <p class="eyebrow">Olá</p>
      <h1 class="page-title"><?= Helpers::e($user['name']) ?></h1>
    </div>
    <form method="post" action="/logout">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <button class="btn btn-outline" type="submit">Sair</button>
    </form>
  </div>
  <h2 style="font-family:var(--serif);font-weight:500">Meus pedidos</h2>
  <?php if (!$orders): ?>
    <div class="empty-state"><p>Você ainda não fez pedidos.</p><a class="btn" href="/catalogo">Começar a comprar</a></div>
  <?php else: ?>
    <table class="cart-table">
      <thead><tr><th>Pedido</th><th>Data</th><th>Status</th><th>Total</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= Helpers::e($o['number']) ?></td>
          <td><?= Helpers::e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
          <td><?= Helpers::e($o['status']) ?></td>
          <td><?= Helpers::money($o['total']) ?></td>
          <td><a href="/conta/pedido/<?= Helpers::e($o['number']) ?>">Detalhes</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

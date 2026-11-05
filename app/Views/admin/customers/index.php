<?php use App\Core\Helpers; ?>
<h1 class="page-title">Clientes</h1>
<table class="admin-table">
  <thead><tr><th>Nome</th><th>Contato</th><th>Pedidos</th><th>Total gasto</th><th>Último pedido</th></tr></thead>
  <tbody>
  <?php foreach ($customers as $c): ?>
    <tr>
      <td><?= Helpers::e($c['name']) ?></td>
      <td><?= Helpers::e($c['email']) ?><br><span class="muted"><?= Helpers::e($c['phone'] ?? '-') ?></span></td>
      <td><?= (int)$c['orders_count'] ?></td>
      <td><?= Helpers::money($c['total_spent']) ?></td>
      <td><?= $c['last_order_at'] ? Helpers::e(date('d/m/Y', strtotime($c['last_order_at']))) : '-' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

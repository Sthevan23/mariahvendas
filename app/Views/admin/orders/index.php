<?php use App\Core\Helpers; ?>
<div class="section-head">
  <h1 class="page-title">Pedidos</h1>
  <form method="get">
    <select class="form-control" name="status" onchange="this.form.submit()">
      <option value="">Todos</option>
      <?php foreach (['awaiting_confirmation','awaiting_payment','payment_approved','separating','shipped','delivered','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>
<table class="admin-table">
  <thead><tr><th>Nº</th><th>Cliente</th><th>Data</th><th>Total</th><th>Status</th><th>Pagamento</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><?= Helpers::e($o['number']) ?></td>
      <td><?= Helpers::e($o['customer_name']) ?></td>
      <td><?= Helpers::e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
      <td><?= Helpers::money($o['total']) ?></td>
      <td><?= Helpers::e($o['status']) ?></td>
      <td><?= Helpers::e($o['payment_status']) ?></td>
      <td><a href="/admin/pedidos/<?= (int)$o['id'] ?>">Abrir</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

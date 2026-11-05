<?php use App\Core\Helpers; ?>
<h1 class="page-title">Dashboard</h1>
<div class="stat-grid">
  <div class="stat-card"><span>Faturamento hoje</span><strong><?= Helpers::money($revenueToday) ?></strong></div>
  <div class="stat-card"><span>Semanal</span><strong><?= Helpers::money($revenueWeek) ?></strong></div>
  <div class="stat-card"><span>Mensal</span><strong><?= Helpers::money($revenueMonth) ?></strong></div>
  <div class="stat-card"><span>Ticket médio</span><strong><?= Helpers::money($ticket) ?></strong></div>
</div>
<p class="muted">Pedidos no mês: <?= (int)$ordersMonth ?> (somente pagos entram no faturamento)</p>

<div class="admin-card">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Vendas (14 dias)</h3>
  <?php
    $max = max(1, ...array_map(fn($r) => (float)$r['total'], $chart ?: [['total' => 1]]));
  ?>
  <div class="chart-bars">
    <?php foreach ($chart as $row): ?>
      <div style="height:<?= max(4, ((float)$row['total'] / $max) * 100) ?>%" title="<?= Helpers::e($row['day']) ?>: <?= Helpers::money($row['total']) ?>"></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="catalog-layout">
  <div class="admin-card">
    <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Mais vendidos</h3>
    <?php foreach ($topProducts as $p): ?>
      <p><?= Helpers::e($p['product_name']) ?> — <?= (int)$p['qty'] ?> un · <?= Helpers::money($p['revenue']) ?></p>
    <?php endforeach; ?>
    <?php if (!$topProducts): ?><p class="muted">Sem vendas pagas ainda.</p><?php endif; ?>
  </div>
  <div class="admin-card">
    <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Estoque baixo</h3>
    <?php foreach ($lowStock as $s): ?>
      <p><?= Helpers::e($s['product_name']) ?> <?= Helpers::e($s['size']) ?>/<?= Helpers::e($s['color']) ?>: <strong><?= (int)$s['stock'] ?></strong></p>
    <?php endforeach; ?>
  </div>
</div>

<div class="admin-card">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Aguardando atendimento</h3>
  <?php foreach ($awaiting as $o): ?>
    <p><a href="/admin/pedidos/<?= (int)$o['id'] ?>"><?= Helpers::e($o['number']) ?></a> — <?= Helpers::e($o['customer_name']) ?> — <?= Helpers::money($o['total']) ?> — <?= Helpers::e($o['status']) ?></p>
  <?php endforeach; ?>
  <?php if (!$awaiting): ?><p class="muted">Nenhum pedido pendente.</p><?php endif; ?>
</div>

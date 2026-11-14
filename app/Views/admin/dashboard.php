<?php use App\Core\Helpers; ?>
<div class="admin-page-head">
  <h1 class="page-title">Dashboard</h1>
</div>

<div class="admin-quick">
  <a href="/admin/produtos"><strong>Produtos</strong>Cadastrar e editar vestidos</a>
  <a href="/admin/pedidos"><strong>Pedidos</strong>Status e atendimento</a>
  <a href="/admin/estoque"><strong>Estoque</strong>Tamanhos e cores</a>
  <a href="/admin/configuracoes#capa"><strong>Trocar capa do site</strong>Foto e texto do banner da home</a>
  <a href="/admin/configuracoes"><strong>Configurações</strong>Contatos e loja</a>
</div>

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
    <?php if ($topProducts): ?>
      <?php foreach ($topProducts as $p): ?>
        <p><?= Helpers::e($p['product_name']) ?> — <?= (int)$p['qty'] ?> un · <?= Helpers::money($p['revenue']) ?></p>
      <?php endforeach; ?>
    <?php elseif ($topCatalog): ?>
      <p class="muted" style="margin-top:0;font-size:.85rem">Pedidos pagos ainda não registrados — destaques do catálogo:</p>
      <?php foreach ($topCatalog as $p): ?>
        <p><?= Helpers::e($p['product_name']) ?> — <?= (int)$p['qty'] ?> un · ref. <?= Helpers::money($p['revenue']) ?></p>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="muted">Cadastre produtos ou aguarde as primeiras vendas pagas.</p>
    <?php endif; ?>
  </div>
  <div class="admin-card">
    <div class="section-head" style="margin-bottom:.75rem">
      <h3 style="margin:0;font-family:var(--serif);font-weight:500">Estoque baixo</h3>
      <a href="/admin/estoque" class="muted" style="font-size:.82rem">Ajustar</a>
    </div>
    <?php if ($lowStock): ?>
      <p class="muted" style="margin-top:0;font-size:.82rem">Produtos com alguma variante com ≤ <?= (int)$lowStockThreshold ?> un.</p>
      <ul class="admin-dash-list">
        <?php foreach ($lowStock as $s): ?>
          <li>
            <a href="/admin/produtos/<?= (int)$s['product_id'] ?>/editar"><?= Helpers::e($s['product_name']) ?></a>
            — mín. <strong><?= (int)$s['stock'] ?></strong> un
            <?php if ((int)$s['variants_low'] > 1): ?>
              <span class="muted">(<?= (int)$s['variants_low'] ?> variantes)</span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted" style="margin:0">Tudo certo — nenhum produto abaixo de <?= (int)$lowStockThreshold ?> unidades.</p>
    <?php endif; ?>
  </div>
</div>

<div class="admin-card">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Aguardando atendimento</h3>
  <?php foreach ($awaiting as $o): ?>
    <p><a href="/admin/pedidos/<?= (int)$o['id'] ?>"><?= Helpers::e($o['number']) ?></a> — <?= Helpers::e($o['customer_name']) ?> — <?= Helpers::money($o['total']) ?> — <?= Helpers::e($o['status']) ?></p>
  <?php endforeach; ?>
  <?php if (!$awaiting): ?><p class="muted">Nenhum pedido pendente.</p><?php endif; ?>
</div>

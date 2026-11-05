<?php use App\Core\Helpers; ?>
<div class="section-head">
  <h1 class="page-title">Financeiro</h1>
  <a class="btn btn-outline" href="/admin/financeiro/exportar?from=<?= Helpers::e($from) ?>&to=<?= Helpers::e($to) ?>">Exportar CSV</a>
</div>
<form method="get" class="form-grid two" style="margin:1rem 0">
  <div class="form-group"><label>De</label><input class="form-control" type="date" name="from" value="<?= Helpers::e($from) ?>"></div>
  <div class="form-group"><label>Até</label><input class="form-control" type="date" name="to" value="<?= Helpers::e($to) ?>"></div>
  <button class="btn" type="submit">Filtrar</button>
</form>
<div class="stat-grid">
  <div class="stat-card"><span>Receitas pagas</span><strong><?= Helpers::money($revenue) ?></strong></div>
  <div class="stat-card"><span>CMV estimado</span><strong><?= Helpers::money($cost) ?></strong></div>
  <div class="stat-card"><span>Despesas</span><strong><?= Helpers::money($expensesTotal) ?></strong></div>
  <div class="stat-card"><span>Lucro estimado</span><strong><?= Helpers::money($profit) ?></strong></div>
</div>
<div class="admin-card">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Nova despesa</h3>
  <form method="post" action="/admin/financeiro/despesa" class="form-grid two">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <div class="form-group"><label>Descrição</label><input class="form-control" name="description" required></div>
    <div class="form-group"><label>Valor</label><input class="form-control" name="amount" required></div>
    <div class="form-group"><label>Categoria</label><input class="form-control" name="category"></div>
    <div class="form-group"><label>Data</label><input class="form-control" type="date" name="expense_date" value="<?= date('Y-m-d') ?>"></div>
    <button class="btn" type="submit">Salvar</button>
  </form>
</div>
<div class="admin-card">
  <h3 style="margin-top:0;font-family:var(--serif);font-weight:500">Margem por produto</h3>
  <table class="admin-table">
    <thead><tr><th>Produto</th><th>Preço</th><th>Custo</th><th>Margem</th></tr></thead>
    <tbody>
    <?php foreach ($margins as $m): ?>
      <tr>
        <td><?= Helpers::e($m['name']) ?></td>
        <td><?= Helpers::money($m['price']) ?></td>
        <td><?= Helpers::money($m['cost_price']) ?></td>
        <td><?= Helpers::e((string)$m['margin']) ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

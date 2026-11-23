<?php
use App\Core\Helpers;
$s = $stats;
$max = max(1, ...array_map(fn($r) => $r['visitors'], $s['series']));
$totalDev = max(1, array_sum(array_map(fn($d) => (int) $d['visitors'], $s['devices'])));
$devName = ['mobile' => 'Celular', 'desktop' => 'Computador', 'tablet' => 'Tablet'];
$pageName = static function (string $p): string {
    if ($p === '/') return 'Página inicial';
    if ($p === '/catalogo') return 'Catálogo';
    if (str_starts_with($p, '/produto/')) return 'Vestido: ' . substr($p, 9);
    if (str_starts_with($p, '/categoria/')) return 'Categoria: ' . substr($p, 11);
    return $p;
};
?>
<div class="admin-page-head">
  <h1 class="page-title">Visitas do site</h1>
  <div style="display:flex;gap:.4rem;flex-wrap:wrap">
    <?php foreach ([7 => '7 dias', 30 => '30 dias', 90 => '90 dias'] as $d => $label): ?>
      <a class="btn <?= $s['days'] === $d ? '' : 'btn-outline' ?>" href="/admin/visitas?dias=<?= $d ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><span>Pessoas hoje</span><strong><?= $s['visitors_today'] ?></strong></div>
  <div class="stat-card"><span>Pessoas (<?= $s['days'] ?> dias)</span><strong><?= $s['visitors'] ?></strong></div>
  <div class="stat-card"><span>Páginas vistas (<?= $s['days'] ?> dias)</span><strong><?= $s['views'] ?></strong></div>
  <div class="stat-card"><span>Voltaram ao site</span><strong><?= $s['returning'] ?></strong></div>
</div>
<p class="muted">
  Cada pessoa conta uma vez por aparelho. Acessos seus (logada no painel) e de robôs não entram.
  <?php if ($s['first_day']): ?>Contagem desde <?= date('d/m/Y', strtotime($s['first_day'])) ?> · total de <?= $s['visitors_total'] ?> pessoas.<?php else: ?>A contagem começa agora: as primeiras visitas aparecem aqui assim que alguém entrar no site.<?php endif; ?>
</p>

<div class="admin-card">
  <h3>Pessoas por dia</h3>
  <div class="chart-bars" style="height:180px">
    <?php foreach ($s['series'] as $r): ?>
      <div style="height:<?= max(3, ($r['visitors'] / $max) * 100) ?>%" title="<?= date('d/m', strtotime($r['day'])) ?>: <?= $r['visitors'] ?> pessoas · <?= $r['views'] ?> páginas"></div>
    <?php endforeach; ?>
  </div>
  <p class="muted" style="font-size:.8rem;margin:.5rem 0 0">Passe o dedo/mouse numa barra para ver o dia.</p>
</div>

<div class="catalog-layout">
  <div class="admin-card">
    <h3>Páginas mais visitadas</h3>
    <?php if ($s['pages']): foreach ($s['pages'] as $p): ?>
      <p style="display:flex;justify-content:space-between;gap:1rem;margin:.35rem 0"><span><?= Helpers::e($pageName($p['path'])) ?></span><strong><?= (int) $p['views'] ?></strong></p>
    <?php endforeach; else: ?><p class="muted">Sem dados ainda.</p><?php endif; ?>
  </div>
  <div class="admin-card">
    <h3>Vestidos mais vistos</h3>
    <?php if ($s['products']): foreach ($s['products'] as $p): ?>
      <p style="display:flex;justify-content:space-between;gap:1rem;margin:.35rem 0"><span><?= Helpers::e($p['name']) ?></span><strong><?= (int) $p['views'] ?></strong></p>
    <?php endforeach; else: ?><p class="muted">Sem dados ainda.</p><?php endif; ?>
  </div>
</div>

<div class="catalog-layout">
  <div class="admin-card">
    <h3>De onde vieram</h3>
    <?php if ($s['sources']): foreach ($s['sources'] as $p): ?>
      <p style="display:flex;justify-content:space-between;gap:1rem;margin:.35rem 0"><span><?= Helpers::e($p['src']) ?></span><strong><?= (int) $p['visitors'] ?></strong></p>
    <?php endforeach; else: ?><p class="muted">Sem dados ainda.</p><?php endif; ?>
    <p class="muted" style="font-size:.8rem">Dica: nos links que você posta no Instagram/WhatsApp, termine com <code>?utm_source=instagram</code> para ver quantos vieram de lá.</p>
  </div>
  <div class="admin-card">
    <h3>Aparelhos</h3>
    <?php if ($s['devices']): foreach ($s['devices'] as $d): ?>
      <p style="display:flex;justify-content:space-between;gap:1rem;margin:.35rem 0"><span><?= Helpers::e($devName[$d['device']] ?? $d['device']) ?></span><strong><?= round($d['visitors'] / $totalDev * 100) ?>%</strong></p>
    <?php endforeach; else: ?><p class="muted">Sem dados ainda.</p><?php endif; ?>
  </div>
</div>

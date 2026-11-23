<?php
use App\Core\Helpers;
$s = $stats;
$days = $s['days'];
$maxV = max(1, ...array_map(fn($r) => $r['visitors'], $s['series']));
$maxH = max(1, ...$s['hours']);
$bestHour = array_search(max($s['hours']), $s['hours']);
$totalDev = max(1, array_sum(array_map(fn($d) => (int) $d['visitors'], $s['devices'])));
$devName = ['mobile' => '📱 Celular', 'desktop' => '💻 Computador', 'tablet' => '📲 Tablet'];

$delta = static function (int $now, int $prev): string {
    if ($prev === 0) {
        return $now > 0 ? '<span class="va-up">novo: ainda não havia dados antes</span>' : '';
    }
    $pct = round(($now - $prev) / $prev * 100);
    if ($pct > 0) return '<span class="va-up">▲ ' . $pct . '% a mais que no período anterior</span>';
    if ($pct < 0) return '<span class="va-down">▼ ' . abs($pct) . '% a menos que no período anterior</span>';
    return '<span class="va-flat">= igual ao período anterior</span>';
};
$fmtTime = static function (int $sec): string {
    if ($sec <= 0) return '—';
    if ($sec < 60) return $sec . ' s';
    return floor($sec / 60) . ' min' . ($sec % 60 ? ' ' . ($sec % 60) . ' s' : '');
};
$kindIcon = ['whatsapp' => '💬', 'carrinho' => '🛒', 'comprar' => '💳', 'tamanho' => '📏', 'cor' => '🎨', 'menu' => '☰', 'favorito' => '❤️', 'link' => '🔗', 'botao' => '👆'];
$pageName = static function (string $p): string {
    if ($p === '/') return '🏠 Página inicial';
    if ($p === '/catalogo') return '👗 Catálogo de vestidos';
    if (str_starts_with($p, '/produto/')) return '🛍️ Vestido: ' . ucwords(str_replace('-', ' ', substr($p, 9)));
    if (str_starts_with($p, '/categoria/')) return '🗂️ Categoria: ' . ucwords(str_replace('-', ' ', substr($p, 11)));
    return $p;
};
$srcName = static function (string $src): string {
    $l = strtolower($src);
    if ($src === 'Acesso direto') return '🔗 Acesso direto (digitou o endereço ou abriu de um app)';
    if (str_contains($l, 'instagram')) return '📸 Instagram';
    if (str_contains($l, 'google')) return '🔎 Google';
    if (str_contains($l, 'facebook') || $l === 'fb') return '👍 Facebook';
    if (str_contains($l, 'whatsapp') || $l === 'wa.me') return '💬 WhatsApp';
    if (str_contains($l, 'tiktok')) return '🎵 TikTok';
    return '🌐 ' . $src;
};
$bars = static function (array $rows, string $labelKey, string $valKey, callable $label): void {
    $max = max(1, ...array_map(fn($r) => (int) $r[$valKey], $rows ?: [[$valKey => 1]]));
    foreach ($rows as $r) {
        $v = (int) $r[$valKey];
        echo '<div class="va-row"><div class="va-row-top"><span>' . Helpers::e($label($r[$labelKey])) . '</span><strong>' . $v . '</strong></div>'
            . '<div class="va-track"><i style="width:' . max(3, round($v / $max * 100)) . '%"></i></div></div>';
    }
};
?>
<style>
  .va-tabs{display:flex;gap:.4rem;flex-wrap:wrap}
  .va-hero{display:grid;gap:1rem;grid-template-columns:1.4fr 1fr;margin-bottom:1.25rem}
  @media(max-width:760px){.va-hero{grid-template-columns:1fr}}
  .va-big{background:#1a1412;color:#fff;padding:1.5rem;border-radius:4px}
  .va-big small{opacity:.7;display:block;font-size:.85rem}
  .va-big .n{font-family:var(--serif,serif);font-size:3.6rem;line-height:1;margin:.3rem 0}
  .va-online{display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.12);padding:.35rem .8rem;border-radius:99px;font-size:.88rem;margin-top:.6rem}
  .va-dot{width:.6rem;height:.6rem;border-radius:50%;background:#3ddc84;animation:vap 1.6s infinite}
  .va-dot.off{background:#999;animation:none}
  @keyframes vap{0%{box-shadow:0 0 0 0 rgba(61,220,132,.7)}100%{box-shadow:0 0 0 10px rgba(61,220,132,0)}}
  .va-cards{display:grid;gap:.75rem;grid-template-columns:1fr 1fr}
  .va-card{background:#fff;border:1px solid var(--line,#e5dfd6);padding:1rem;border-radius:4px}
  .va-card strong{display:block;font-size:1.9rem;font-family:var(--serif,serif);font-weight:500}
  .va-card span.l{font-size:.82rem;color:#6b625b}
  .va-card em{display:block;font-style:normal;font-size:.75rem;margin-top:.25rem}
  .va-up{color:#1f8a4c}.va-down{color:#c0392b}.va-flat{color:#8a817a}
  .va-help{background:#fbf7f0;border:1px solid #eadfcc;padding:1rem 1.25rem;margin-bottom:1.25rem;font-size:.9rem;line-height:1.55;border-radius:4px}
  .va-help summary{cursor:pointer;font-weight:500}
  .va-chart{display:flex;align-items:flex-end;gap:3px;height:170px;padding-top:1.4rem}
  .va-col{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;position:relative;min-width:0}
  .va-col i{display:block;width:100%;background:#d9b99b;border-radius:3px 3px 0 0;min-height:3px;transition:height .5s}
  .va-col.today i{background:#1a1412}
  .va-col b{position:absolute;top:-1.2rem;font-size:.68rem;font-weight:500}
  .va-col:hover i{background:#b8895f}
  .va-axis{display:flex;justify-content:space-between;font-size:.72rem;color:#8a817a;margin-top:.35rem}
  .va-row{margin:.6rem 0}.va-row-top{display:flex;justify-content:space-between;gap:1rem;font-size:.9rem;margin-bottom:.2rem}
  .va-track{height:8px;background:#f1ebe3;border-radius:99px;overflow:hidden}
  .va-track i{display:block;height:100%;background:linear-gradient(90deg,#d9b99b,#b8895f);border-radius:99px}
  .va-sub{font-size:.8rem;color:#8a817a;margin:-.4rem 0 .6rem}
  .va-live{font-size:.78rem;color:#8a817a;text-align:right;margin:-.5rem 0 1rem}
</style>

<div class="admin-page-head">
  <h1 class="page-title">Visitas do site</h1>
  <div class="va-tabs">
    <?php foreach ([7 => 'Últimos 7 dias', 30 => 'Últimos 30 dias', 90 => 'Últimos 90 dias'] as $d => $label): ?>
      <a class="btn <?= $days === $d ? '' : 'btn-outline' ?>" href="/admin/visitas?dias=<?= $d ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div id="va-area">
  <div class="va-hero">
    <div class="va-big">
      <small>Hoje, <?= date('d/m/Y') ?></small>
      <div class="n"><?= $s['visitors_today'] ?></div>
      <div><?= $s['visitors_today'] === 1 ? 'pessoa visitou' : 'pessoas visitaram' ?> o site hoje<?= $s['views_today'] > 0 ? ' · ' . $s['views_today'] . ' páginas abertas' : '' ?></div>
      <div class="va-online"><span class="va-dot <?= $s['online'] ? '' : 'off' ?>"></span>
        <?= $s['online'] ? '<b>' . $s['online'] . '</b> ' . ($s['online'] === 1 ? 'pessoa no site agora' : 'pessoas no site agora') : 'Ninguém no site neste momento' ?>
      </div>
    </div>
    <div class="va-cards">
      <div class="va-card"><span class="l">👥 Pessoas nos últimos <?= $days ?> dias</span><strong><?= $s['visitors'] ?></strong><em><?= $delta($s['visitors'], $s['prev_visitors']) ?></em></div>
      <div class="va-card"><span class="l">📄 Páginas vistas</span><strong><?= $s['views'] ?></strong><em><?= $delta($s['views'], $s['prev_views']) ?></em></div>
      <div class="va-card"><span class="l">⏱️ Tempo médio no site</span><strong><?= $fmtTime($s['avg_seconds']) ?></strong><em class="va-flat">por visita<?= $s['avg_seconds'] ? '' : ' (aparece após as primeiras visitas)' ?></em></div>
      <div class="va-card"><span class="l">📄 Páginas por visita</span><strong><?= $s['pages_per_visit'] ?></strong><em class="va-flat"><?= $s['bounce'] ?> de <?= $s['sessions'] ?> saíram sem ver mais nada</em></div>
      <div class="va-card"><span class="l">🔁 Voltaram outro dia</span><strong><?= $s['returning'] ?></strong><em class="va-flat">gostaram e voltaram</em></div>
      <div class="va-card"><span class="l">💬 Cliques no WhatsApp</span><strong><?= $s['whatsapp'] ?></strong><em class="va-flat"><?= $s['cart'] ?> cliques em sacola/comprar</em></div>
    </div>
  </div>

  <?php if ($s['visitors_total'] === 0): ?>
    <div class="va-help"><b>Ainda sem visitas registradas.</b> A contagem começou agora: assim que alguém abrir o site, os números aparecem aqui sozinhos (esta página se atualiza a cada 30 segundos).</div>
  <?php endif; ?>

  <div class="admin-card">
    <h3>Quantas pessoas entraram por dia</h3>
    <p class="va-sub">Cada barra é um dia. A barra escura é hoje. Passe o mouse (ou toque) para ver os detalhes.</p>
    <div class="va-chart">
      <?php $n = count($s['series']); foreach ($s['series'] as $i => $r): ?>
        <div class="va-col <?= $i === $n - 1 ? 'today' : '' ?>" title="<?= date('d/m', strtotime($r['day'])) ?> — <?= $r['visitors'] ?> pessoas · <?= $r['views'] ?> páginas">
          <?php if ($days <= 30 && $r['visitors'] > 0): ?><b><?= $r['visitors'] ?></b><?php endif; ?>
          <i style="height:<?= max(2, round($r['visitors'] / $maxV * 100)) ?>%"></i>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="va-axis"><span><?= date('d/m', strtotime($s['series'][0]['day'])) ?></span><span>hoje</span></div>
  </div>

  <div class="catalog-layout">
    <div class="admin-card">
      <h3>De onde as pessoas chegaram</h3>
      <p class="va-sub">Quantas pessoas vieram de cada lugar.</p>
      <?php if ($s['sources']): $bars($s['sources'], 'src', 'visitors', $srcName); else: ?><p class="muted">Sem dados ainda.</p><?php endif; ?>
      <p class="muted" style="font-size:.8rem;margin-top:1rem">💡 Para saber quantos vieram do Instagram, coloque <code>?utm_source=instagram</code> no fim do link que você divulga lá.</p>
    </div>
    <div class="admin-card">
      <h3>Vestidos mais olhados</h3>
      <p class="va-sub">Quantas vezes cada vestido foi aberto.</p>
      <?php if ($s['products']): $bars($s['products'], 'name', 'views', fn($x) => '👗 ' . $x); else: ?><p class="muted">Sem dados ainda.</p><?php endif; ?>
    </div>
  </div>

  <div class="catalog-layout">
    <div class="admin-card">
      <h3>Páginas mais visitadas</h3>
      <p class="va-sub">Quantas vezes cada página foi aberta e quanto tempo as pessoas ficam nela.</p>
      <?php if ($s['pages']): foreach ($s['pages'] as $r) { echo '<div class="va-row"><div class="va-row-top"><span>' . Helpers::e($pageName($r['path'])) . ($r['secs'] ? ' <small style="color:#8a817a">· fica ' . $fmtTime((int) $r['secs']) . '</small>' : '') . '</span><strong>' . (int) $r['views'] . '</strong></div><div class="va-track"><i style="width:' . max(3, round($r['views'] / max(1, (int) $s['pages'][0]['views']) * 100)) . '%"></i></div></div>'; } else: ?><p class="muted">Sem dados ainda.</p><?php endif; ?>
    </div>
    <div class="admin-card">
      <h3>Em que aparelho entram</h3>
      <p class="va-sub">Mostra se o site precisa estar bonito principalmente no celular.</p>
      <?php foreach ($s['devices'] as $d): $pc = round($d['visitors'] / $totalDev * 100); ?>
        <div class="va-row"><div class="va-row-top"><span><?= $devName[$d['device']] ?? Helpers::e($d['device']) ?></span><strong><?= $pc ?>%</strong></div><div class="va-track"><i style="width:<?= max(3, $pc) ?>%"></i></div></div>
      <?php endforeach; if (!$s['devices']): ?><p class="muted">Sem dados ainda.</p><?php endif; ?>
    </div>
  </div>

  <div class="catalog-layout">
    <div class="admin-card">
      <h3>De que cidade são</h3>
      <p class="va-sub">Cidade aproximada (pelo provedor de internet; no celular pode mostrar a cidade da operadora).</p>
      <?php if ($s['cities']): ?>
        <?php $bars($s['cities'], 'city', 'visitors', fn($x) => '📍 ' . $x); ?>
        <p class="muted" style="font-size:.8rem;margin-top:.8rem">Cidade identificada para <?= $s['geo_known'] ?> de <?= $s['visitors'] ?> pessoas.</p>
      <?php else: ?><p class="muted">Aparece aqui nas próximas visitas (a cidade é descoberta quando a pessoa entra no site).</p><?php endif; ?>
    </div>
    <div class="admin-card">
      <h3>Botões e links mais clicados</h3>
      <p class="va-sub">O que as pessoas tocam no site, do mais clicado para o menos.</p>
      <?php if ($s['clicks']): foreach ($s['clicks'] as $c): $mx = (int) $s['clicks'][0]['total']; ?>
        <div class="va-row"><div class="va-row-top"><span><?= $kindIcon[$c['kind']] ?? '👆' ?> <?= Helpers::e($c['label']) ?></span><strong><?= (int) $c['total'] ?>×</strong></div>
        <div class="va-track"><i style="width:<?= max(3, round($c['total'] / max(1, $mx) * 100)) ?>%"></i></div></div>
      <?php endforeach; else: ?><p class="muted">Aparece aqui assim que alguém clicar em algo no site.</p><?php endif; ?>
    </div>
  </div>

  <div class="admin-card">
    <h3>Em que horário o site é mais visitado</h3>
    <p class="va-sub"><?= max($s['hours']) > 0 ? 'O horário mais movimentado é por volta das <b>' . $bestHour . 'h</b>. Bom horário para postar no Instagram e responder o WhatsApp.' : 'Aparece aqui assim que houver visitas.' ?></p>
    <div class="va-chart" style="height:120px">
      <?php foreach ($s['hours'] as $h => $v): ?>
        <div class="va-col <?= $h === $bestHour && $v > 0 ? 'today' : '' ?>" title="<?= $h ?>h — <?= $v ?> pessoas"><i style="height:<?= $v ? max(3, round($v / $maxH * 100)) : 2 ?>%"></i></div>
      <?php endforeach; ?>
    </div>
    <div class="va-axis"><span>0h</span><span>6h</span><span>12h</span><span>18h</span><span>23h</span></div>
  </div>

  <p class="va-live">🏆 Total desde o início: <?= $s['visitors_total'] ?> pessoas<?= $s['first_day'] ? ' (desde ' . date('d/m/Y', strtotime($s['first_day'])) . ')' : '' ?> · 🔄 Atualiza sozinho a cada 30 segundos · última leitura às <?= date('H:i:s') ?></p>
</div>

<details class="va-help">
  <summary>Como ler estes números (explicação simples)</summary>
  <p><b>Pessoas</b> = quantas pessoas diferentes entraram (cada celular/computador conta uma vez, mesmo que abra o site várias vezes).<br>
  <b>Páginas vistas</b> = quantas telas foram abertas no total. Se 1 pessoa olha 5 vestidos, são 1 pessoa e 5 páginas.<br>
  <b>Voltaram outro dia</b> = pessoas que entraram em mais de um dia diferente, sinal de interesse.<br>
  <b>No site agora</b> = quem abriu alguma página nos últimos 5 minutos.<br>
  <b>Tempo médio no site</b> = quanto tempo a pessoa fica com o site aberto na tela, somando todas as páginas da visita.<br>
  <b>Páginas por visita</b> = quantas telas cada pessoa abre em média. "Saíram sem ver mais nada" = abriram só 1 página e foram embora.<br>
  <b>Cidade</b> = descoberta pelo endereço de internet da pessoa; o endereço em si não é guardado.<br>
  <b>Cliques</b> = botões e links tocados (WhatsApp, sacola, tamanhos, cores, menu...).<br>
  <b>Comparação</b> = o período atual contra o período anterior de mesmo tamanho (ex.: 30 dias contra os 30 dias antes).<br>
  Não contam: suas visitas com o painel aberto, robôs do Google e cliques dentro do próprio painel.</p>
</details>

<script>
(function () {
  var url = location.pathname + location.search;
  setInterval(function () {
    if (document.hidden) return;
    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        var d = new DOMParser().parseFromString(html, 'text/html').getElementById('va-area');
        var cur = document.getElementById('va-area');
        if (d && cur) cur.innerHTML = d.innerHTML;
      }).catch(function () {});
  }, 30000);
})();
</script>

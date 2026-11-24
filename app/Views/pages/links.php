<?php
use App\Core\Helpers;

$store = (string) Helpers::setting('store_name', 'Ateliê Mariah');
$tag = (string) Helpers::setting('tagline', 'Alta costura e vestidos de festa');
$base = Helpers::appUrl();
$ig = ltrim((string) Helpers::setting('instagram', ''), '@');
$wa = Helpers::whatsappLink('Olá! Vim pelo link do Instagram e quero conhecer os vestidos.');

$links = [
    ['t' => 'Falar no WhatsApp', 's' => 'Atendimento direto com a Mariah', 'u' => $wa, 'primary' => true, 'ext' => true],
    ['t' => 'Ver o catálogo completo', 's' => 'Todos os vestidos de festa', 'u' => '/catalogo'],
    ['t' => 'Novidades', 's' => 'Peças recém-chegadas', 'u' => '/categoria/novidades'],
    ['t' => 'Coleção Festa', 's' => 'Festa e formatura', 'u' => '/categoria/colecao-atelier'],
];
if ($ig !== '') {
    $links[] = ['t' => 'Instagram', 's' => '@' . $ig, 'u' => 'https://instagram.com/' . rawurlencode($ig), 'ext' => true];
}
$links[] = ['t' => 'Trocas e entregas', 's' => 'Como funciona', 'u' => '/trocas'];

$logo = Helpers::imageOrDefault((string) Helpers::setting('hero_image', ''), '/uploads/moda/hero-atelier.jpg');
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Links | <?= Helpers::e($store) ?></title>
<meta name="description" content="<?= Helpers::e($store . ' — ' . $tag) ?>. Catálogo, WhatsApp e Instagram em um só lugar.">
<link rel="canonical" href="<?= Helpers::e($base . '/links') ?>">
<meta name="theme-color" content="#f5f0ea">
<meta property="og:title" content="<?= Helpers::e($store) ?>">
<meta property="og:description" content="<?= Helpers::e($tag) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= Helpers::e($base . '/links') ?>">
<style>
:root{--bg:#f5f0ea;--surface:#fffcf9;--ink:#14110f;--muted:#4a433c;--gold:#b8956b;--eyebrow:#7d5a3c}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;background:var(--bg);color:var(--ink);font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;display:flex;justify-content:center;padding:2.5rem 1rem 3rem}
.wrap{width:100%;max-width:440px;text-align:center}
.avatar{width:104px;height:104px;border-radius:50%;object-fit:cover;border:3px solid var(--gold);background:#ebe3d9}
h1{font-family:Georgia,"Times New Roman",serif;font-weight:500;font-size:1.7rem;margin:1rem 0 .25rem}
.tag{margin:0 0 1.8rem;color:var(--eyebrow);font-size:.95rem}
.list{display:flex;flex-direction:column;gap:.75rem}
.btn{display:block;padding:1rem 1.1rem;border:1px solid var(--gold);border-radius:14px;background:var(--surface);color:var(--ink);text-decoration:none;transition:transform .15s,box-shadow .15s}
.btn:hover,.btn:focus-visible{transform:translateY(-2px);box-shadow:0 6px 18px rgba(20,17,15,.12)}
.btn:focus-visible{outline:2px solid var(--ink);outline-offset:2px}
.btn b{display:block;font-size:1.02rem}
.btn small{display:block;margin-top:.15rem;color:var(--muted);font-size:.85rem}
.btn.primary{background:var(--ink);border-color:var(--ink);color:#fff}
.btn.primary small{color:#e6ddd2}
.foot{margin-top:2rem;font-size:.8rem;color:var(--muted)}
.foot a{color:var(--eyebrow)}
</style>
</head>
<body>
<main class="wrap">
  <img class="avatar" src="<?= Helpers::e($logo) ?>" alt="<?= Helpers::e($store) ?>" width="104" height="104">
  <h1><?= Helpers::e($store) ?></h1>
  <p class="tag"><?= Helpers::e($tag) ?></p>
  <nav class="list" aria-label="Links">
    <?php foreach ($links as $l): ?>
      <a class="btn<?= !empty($l['primary']) ? ' primary' : '' ?>" href="<?= Helpers::e($l['u']) ?>"<?= !empty($l['ext']) ? ' target="_blank" rel="noopener"' : '' ?>>
        <b><?= Helpers::e($l['t']) ?></b><small><?= Helpers::e($l['s']) ?></small>
      </a>
    <?php endforeach; ?>
  </nav>
  <p class="foot"><a href="/">Ir para a loja</a> · <a href="/privacidade">Privacidade</a></p>
</main>
<?php if (\App\Services\Analytics::pvId() > 0): ?>
<script>window.MV_PV=<?= \App\Services\Analytics::pvId() ?>;</script>
<script src="/assets/js/track.js?v=1" defer></script>
<?php endif; ?>
</body>
</html>

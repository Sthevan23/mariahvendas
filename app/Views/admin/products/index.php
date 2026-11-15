<?php use App\Core\Helpers;
$csrf = Helpers::e($_SESSION['_csrf'] ?? '');
$tabs = ['todos' => 'Todos', 'site' => 'No site', 'removidos' => 'Removidos', 'esgotados' => 'Esgotados'];
?>
<div class="section-head">
  <h1 class="page-title">Vestidos (<?= (int) $counts['todos'] ?>)</h1>
  <a class="btn" href="/admin/produtos/novo">Novo vestido</a>
</div>

<div style="display:flex;gap:.4rem;flex-wrap:wrap;margin:.5rem 0">
  <?php foreach ($tabs as $k => $label): ?>
    <a class="btn <?= $status === $k ? '' : 'btn-outline' ?>" style="padding:.35rem .8rem;font-size:.82rem" href="/admin/produtos?status=<?= $k ?><?= !empty($_GET['q']) ? '&q=' . urlencode($_GET['q']) : '' ?>"><?= $label ?> (<?= (int) $counts[$k] ?>)</a>
  <?php endforeach; ?>
</div>

<form method="get" style="margin:1rem 0;display:flex;gap:.5rem">
  <input type="hidden" name="status" value="<?= Helpers::e($status) ?>">
  <input class="form-control" name="q" placeholder="Buscar por nome..." value="<?= Helpers::e($_GET['q'] ?? '') ?>">
  <button class="btn btn-outline" type="submit">Buscar</button>
</form>

<?php if (!$products): ?><p class="muted">Nenhum vestido nesta lista.</p><?php endif; ?>
<div class="admin-table-wrap">
<table class="admin-table">
  <thead><tr><th>Vestido</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td>
        <div class="cart-item">
          <?php if (!empty($p['image'])): ?><img src="<?= Helpers::e($p['image']) ?>" alt=""><?php endif; ?>
          <div>
            <strong><?= Helpers::e($p['name']) ?></strong><br>
            <span class="muted"><?= Helpers::e($p['category_name'] ?? '-') ?></span>
            <?php if ($codes = \App\Models\Product::codes((int) $p['id'])): ?><br><span class="muted">Código: <?= Helpers::e(implode(', ', $codes)) ?></span><?php endif; ?>
          </div>
        </div>
      </td>
      <td><?= Helpers::money($p['price']) ?></td>
      <td><?= (int) $p['stock_total'] > 0 ? (int) $p['stock_total'] : '<strong style="color:#b3261e">Esgotado</strong>' ?></td>
      <td>
        <?php if (!(int) $p['is_active']): ?>Removido do site
        <?php elseif (!$p['has_photo']): ?>Sem foto (não aparece)
        <?php else: ?>No site<?php endif; ?>
      </td>
      <td>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
          <a class="btn btn-outline" style="padding:.3rem .7rem;font-size:.8rem" href="/admin/produtos/<?= (int) $p['id'] ?>/editar">Editar</a>
          <form method="post" action="/admin/produtos/<?= (int) $p['id'] ?>/alternar" style="margin:0">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button class="btn btn-outline" style="padding:.3rem .7rem;font-size:.8rem" type="submit"><?= (int) $p['is_active'] ? 'Remover do site' : 'Voltar ao site' ?></button>
          </form>
          <form method="post" action="/admin/produtos/<?= (int) $p['id'] ?>/excluir" style="margin:0" onsubmit="return confirm('Excluir &quot;<?= Helpers::e(addslashes($p['name'])) ?>&quot; para sempre?\n\nAs fotos e os tamanhos dele serão apagados e NÃO tem como desfazer.\n\nSe só quer tirar do site, use &quot;Remover do site&quot;.');">
            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
            <button class="btn" style="padding:.3rem .7rem;font-size:.8rem;background:#b3261e;border-color:#b3261e;color:#fff" type="submit">Excluir</button>
          </form>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

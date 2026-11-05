<?php use App\Core\Helpers; $isEdit = !empty($product); ?>
<h1 class="page-title"><?= $isEdit ? 'Editar produto' : 'Novo produto' ?></h1>
<form method="post" enctype="multipart/form-data" action="<?= $isEdit ? '/admin/produtos/' . (int)$product['id'] : '/admin/produtos' ?>" class="admin-card form-grid" style="margin-top:1rem">
  <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
  <div class="form-group"><label>Nome</label><input class="form-control" name="name" required value="<?= Helpers::e($product['name'] ?? '') ?>"></div>
  <div class="form-group"><label>Categoria</label>
    <select class="form-control" name="category_id">
      <option value="">Sem categoria</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (($product['category_id'] ?? null) == $c['id']) ? 'selected' : '' ?>><?= Helpers::e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Descrição</label><textarea class="form-control" name="description" rows="5"><?= Helpers::e($product['description'] ?? '') ?></textarea></div>
  <div class="form-grid two">
    <div class="form-group"><label>Preço</label><input class="form-control" name="price" required value="<?= Helpers::e((string)($product['price'] ?? '')) ?>"></div>
    <div class="form-group"><label>Preço anterior</label><input class="form-control" name="compare_at_price" value="<?= Helpers::e((string)($product['compare_at_price'] ?? '')) ?>"></div>
  </div>
  <div class="form-group"><label>Custo</label><input class="form-control" name="cost_price" value="<?= Helpers::e((string)($product['cost_price'] ?? '0')) ?>"></div>
  <div style="display:flex;flex-wrap:wrap;gap:1rem">
    <?php foreach (['is_active'=>'Ativo','is_featured'=>'Destaque','is_new'=>'Novo','is_sale'=>'Promoção','is_bestseller'=>'Mais vendido'] as $k=>$label): ?>
      <label><input type="checkbox" name="<?= $k ?>" value="1" <?= (($product[$k] ?? ($k==='is_active'?1:0)) ? 'checked' : '') ?>> <?= $label ?></label>
    <?php endforeach; ?>
  </div>
  <div class="form-group"><label>URLs de imagens (uma por linha)</label><textarea class="form-control" name="image_urls" rows="3" placeholder="https://..."></textarea></div>
  <div class="form-group"><label>Upload de imagens</label><input type="file" name="images[]" multiple accept="image/*"></div>

  <?php if ($images): ?>
    <div class="instagram-grid">
      <?php foreach ($images as $img): ?><img src="<?= Helpers::e($img['path']) ?>" alt=""><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <h3 style="font-family:var(--serif);font-weight:500">Variações (tamanho / cor / estoque)</h3>
  <div id="variants">
    <?php $rows = $variants ?: [['size'=>'M','color'=>'Nude','color_hex'=>'#D2B48C','stock'=>5]]; foreach ($rows as $v): ?>
      <div class="form-grid two" style="margin-bottom:.5rem">
        <input class="form-control" name="variant_size[]" placeholder="Tamanho" value="<?= Helpers::e($v['size']) ?>">
        <input class="form-control" name="variant_color[]" placeholder="Cor" value="<?= Helpers::e($v['color']) ?>">
        <input class="form-control" name="variant_hex[]" placeholder="#hex" value="<?= Helpers::e($v['color_hex'] ?? '') ?>">
        <input class="form-control" name="variant_stock[]" type="number" min="0" placeholder="Estoque" value="<?= (int)($v['stock'] ?? 0) ?>">
      </div>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-outline" type="button" onclick="document.getElementById('variants').insertAdjacentHTML('beforeend', document.getElementById('variants').children[0].outerHTML)">+ variação</button>
  <button class="btn" type="submit">Salvar</button>
</form>

<?php if ($isEdit): ?>
<form method="post" action="/admin/produtos/<?= (int)$product['id'] ?>/excluir" onsubmit="return confirm('Excluir este produto?')" style="margin-top:1rem">
  <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
  <button class="btn btn-outline" type="submit">Excluir produto</button>
</form>
<?php endif; ?>

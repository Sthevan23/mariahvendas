<?php use App\Core\Helpers; $isEdit = !empty($product); ?>
<h1 class="page-title"><?= $isEdit ? 'Editar produto' : 'Novo produto' ?></h1>

<?php if ($isEdit): ?>
<section class="admin-card" style="margin-top:1rem" aria-labelledby="fotos-titulo">
  <h3 id="fotos-titulo" style="margin-top:0;font-family:var(--serif);font-weight:500">Fotos</h3>
  <p class="muted" style="margin-top:0">A primeira foto é a capa que aparece na loja. Para trocar a capa, clique em "Usar como capa".</p>
  <?php if ($images): ?>
    <div class="photo-manager">
      <?php foreach ($images as $i => $img): ?>
        <figure class="photo-item">
          <img src="<?= Helpers::e($img['path']) ?>" alt="">
          <?php if ($i === 0): ?><span class="tag">Capa</span><?php endif; ?>
          <?php $photoColors = array_values(array_unique(array_map(fn($v) => $v['color'], $variants))); ?>
          <?php if ($photoColors): ?>
            <form method="post" action="/admin/produtos/<?= (int)$product['id'] ?>/imagens/<?= (int)$img['id'] ?>/cor" style="display:flex;gap:.3rem;align-items:center;margin:.4rem 0">
              <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
              <select class="form-control" name="color" aria-label="Cor desta foto" onchange="this.form.submit()">
                <option value="">Todas as cores</option>
                <?php foreach ($photoColors as $pc): ?>
                  <option value="<?= Helpers::e($pc) ?>" <?= mb_strtolower((string)($img['color'] ?? '')) === mb_strtolower((string)$pc) ? 'selected' : '' ?>><?= Helpers::e($pc) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          <?php endif; ?>
          <div class="photo-actions">
            <?php if ($i !== 0): ?>
              <form method="post" action="/admin/produtos/<?= (int)$product['id'] ?>/imagens/<?= (int)$img['id'] ?>/capa">
                <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
                <button class="btn btn-outline" type="submit">Usar como capa</button>
              </form>
            <?php endif; ?>
            <form method="post" action="/admin/produtos/<?= (int)$product['id'] ?>/imagens/<?= (int)$img['id'] ?>/excluir" onsubmit="return confirm('Remover esta foto?')">
              <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
              <button class="btn btn-outline" type="submit">Remover</button>
            </form>
          </div>
        </figure>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted">Este produto ainda não tem fotos. Adicione abaixo, no formulário de dados do produto.</p>
  <?php endif; ?>
</section>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= $isEdit ? '/admin/produtos/' . (int)$product['id'] : '/admin/produtos' ?>" class="admin-card form-grid" style="margin-top:1rem">
  <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
  <div class="form-group"><label for="product-name">Nome</label><input class="form-control" id="product-name" name="name" required value="<?= Helpers::e($product['name'] ?? '') ?>"></div>
  <div class="form-group"><label for="product-category">Categoria</label>
    <select class="form-control" id="product-category" name="category_id">
      <option value="">Sem categoria</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= (($product['category_id'] ?? null) == $c['id']) ? 'selected' : '' ?>><?= Helpers::e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label for="product-description">Descrição</label><textarea class="form-control" id="product-description" name="description" rows="5"><?= Helpers::e($product['description'] ?? '') ?></textarea></div>
  <div class="form-grid two">
    <div class="form-group"><label for="product-price">Preço de venda (R$)</label><input class="form-control" id="product-price" name="price" inputmode="decimal" required value="<?= Helpers::e((string)($product['price'] ?? '')) ?>"></div>
    <div class="form-group"><label for="product-compare-price">Preço "de" (riscado, R$)</label><input class="form-control" id="product-compare-price" name="compare_at_price" inputmode="decimal" value="<?= Helpers::e((string)($product['compare_at_price'] ?? '')) ?>"></div>
  </div>
  <div class="form-group"><label for="product-cost">Custo (R$)</label><input class="form-control" id="product-cost" name="cost_price" inputmode="decimal" value="<?= Helpers::e((string)($product['cost_price'] ?? '0')) ?>"></div>
  <div style="display:flex;flex-wrap:wrap;gap:1rem">
    <?php foreach (['is_active'=>'Ativo','is_featured'=>'Destaque','is_new'=>'Novo','is_sale'=>'Promoção','is_bestseller'=>'Mais vendido'] as $k=>$label): ?>
      <label><input type="checkbox" name="<?= $k ?>" value="1" <?= (($product[$k] ?? ($k==='is_active'?1:0)) ? 'checked' : '') ?>> <?= $label ?></label>
    <?php endforeach; ?>
  </div>
  <?php $allColors = array_values(array_unique(array_map(fn($v) => $v['color'], $variants))); ?>
  <?php if ($allColors): ?>
    <div class="form-group"><label for="new-image-color">As fotos novas são de qual cor?</label>
      <select class="form-control" id="new-image-color" name="new_image_color">
        <option value="">Todas as cores (foto geral)</option>
        <?php foreach ($allColors as $pc): ?><option value="<?= Helpers::e($pc) ?>"><?= Helpers::e($pc) ?></option><?php endforeach; ?>
      </select>
      <p class="muted" style="margin:.25rem 0 0">Na loja, ao clicar na bolinha dessa cor, aparecem as fotos dela. Dá para mudar a cor de cada foto lá em cima, em "Fotos".</p>
    </div>
  <?php endif; ?>
  <div class="form-group"><label for="product-image-urls">Adicionar fotos por link (uma por linha)</label><textarea class="form-control" id="product-image-urls" name="image_urls" rows="3" placeholder="https://..."></textarea></div>
  <div class="form-group"><label for="product-images">Adicionar fotos do computador/celular (JPG, PNG ou WebP, até 12 MB cada)</label><input type="file" id="product-images" name="images[]" multiple accept="image/*"></div>

  <div class="form-group"><label for="product-measures">Medidas deste vestido (aparece na página dele)</label><textarea class="form-control" id="product-measures" name="measures" rows="4" placeholder="P: busto 88 · cintura 68 · comprimento 95&#10;M: busto 92 · cintura 72 · comprimento 96"><?= Helpers::e((string) ($product['measures'] ?? '')) ?></textarea>
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.4rem;align-items:center">
      <span class="muted">Clique para adicionar o tamanho:</span>
      <?php foreach (['P', 'M', 'G', 'GG', 'Único'] as $sz): ?>
        <button type="button" class="btn btn-outline" style="padding:.25rem .7rem" onclick="var t=document.getElementById('product-measures'),s=this.textContent;if(!new RegExp('^'+s+':','m').test(t.value)){t.value=(t.value&&!/\n$/.test(t.value)?t.value+'\n':t.value)+s+': busto  · cintura  · comprimento ';}t.focus()"><?= $sz ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <h3 style="font-family:var(--serif);font-weight:500">Variações (tamanho / cor / estoque)</h3>
  <p class="muted" style="margin:.25rem 0 .75rem">Cada linha é um tamanho de uma cor. Pode digitar vários tamanhos juntos (ex.: <strong>P, M, G</strong>) que o site cria um botão para cada, com o mesmo estoque. Para outra cor, clique em “+ variação”.</p>
  <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.6rem;align-items:center">
    <span class="muted">Clique para adicionar tamanho na linha que você está editando:</span>
    <?php foreach (['P', 'M', 'G', 'GG', 'Único'] as $sz): ?>
      <button type="button" class="btn btn-outline" style="padding:.25rem .7rem" onmousedown="event.preventDefault()" onclick="var f=window.__lastSize||document.querySelector('#variants [name=&quot;variant_size[]&quot;]'),s=this.textContent;if(f){var l=f.value.split(/[\s,;\/|]+/).filter(Boolean);if(l.map(function(x){return x.toUpperCase()}).indexOf(s.toUpperCase())<0){l.push(s);f.value=l.join(', ')}f.focus()}"><?= $sz ?></button>
    <?php endforeach; ?>
  </div>
  <div id="variants" onfocusin="if(event.target.name==='variant_size[]')window.__lastSize=event.target">
    <?php $rows = $variants ?: [['size'=>'M','color'=>'Nude','color_hex'=>'#D2B48C','stock'=>5]]; foreach ($rows as $v): ?>
      <div class="form-grid two" style="margin-bottom:.5rem">
        <input class="form-control" name="variant_size[]" aria-label="Tamanho da variação" placeholder="Tamanho" value="<?= Helpers::e($v['size']) ?>">
        <input class="form-control" name="variant_color[]" aria-label="Cor da variação" placeholder="Cor" value="<?= Helpers::e($v['color']) ?>">
        <?php $hexVal = Helpers::swatchHex($v['color'] ?? '', $v['color_hex'] ?? '');
          if (strlen($hexVal) === 4) { $hexVal = '#' . $hexVal[1] . $hexVal[1] . $hexVal[2] . $hexVal[2] . $hexVal[3] . $hexVal[3]; } ?>
        <div style="display:flex;gap:.5rem;align-items:center">
          <input type="color" aria-label="Escolher a cor (bolinha)" title="Clique para escolher a cor" value="<?= Helpers::e($hexVal) ?>" style="width:2.6rem;height:2.4rem;padding:0;border:1px solid #ccc;border-radius:50%;cursor:pointer;flex:none" oninput="this.nextElementSibling.value=this.value">
          <input class="form-control" name="variant_hex[]" aria-label="Código hex da cor" placeholder="#hex" value="<?= Helpers::e($hexVal) ?>" oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value))this.previousElementSibling.value=this.value">
        </div>
        <input class="form-control" name="variant_stock[]" type="number" min="0" aria-label="Estoque da variação" placeholder="Estoque" value="<?= (int)($v['stock'] ?? 0) ?>">
        <input class="form-control" name="variant_sku[]" aria-label="Código do vestido (anotação interna)" placeholder="Código (ex.: 2475)" value="<?= Helpers::e($v['sku'] ?? '') ?>">
        <button class="btn btn-outline" type="button" onclick="var row=this.parentNode,list=document.getElementById('variants');if(list.children.length>1){row.remove()}else{row.querySelectorAll('input').forEach(function(i){i.value=''})}">Remover</button>
      </div>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-outline" type="button" onclick="var v=document.getElementById('variants'),r=v.lastElementChild.cloneNode(true);r.querySelector('[name=&quot;variant_size[]&quot;]').value='';r.querySelector('[name=&quot;variant_stock[]&quot;]').value='0';v.appendChild(r);r.querySelector('input').focus()">+ variação</button>
  <button class="btn" type="submit">Salvar</button>
</form>

<?php if ($isEdit): ?>
<form method="post" action="/admin/produtos/<?= (int)$product['id'] ?>/excluir" onsubmit="return confirm('Excluir este produto?')" style="margin-top:1rem">
  <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
  <button class="btn btn-outline" type="submit">Excluir produto</button>
</form>
<?php endif; ?>

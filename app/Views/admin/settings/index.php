<?php use App\Core\Helpers;
$heroUrl = $settings['hero_image'] ?? '/uploads/moda/hero-atelier.jpg';
?>
<div class="admin-page-head">
  <h1 class="page-title">Configurações</h1>
</div>

<div class="admin-settings-grid">
  <div class="admin-card">
    <h3 id="capa">Capa do site (banner da home)</h3>
    <p class="muted" style="margin-top:0">Título, texto e foto principal do site.</p>
    <form method="post" action="/admin/configuracoes" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <div class="form-group">
        <label for="settings-hero-title">Título do banner</label>
        <input class="form-control" id="settings-hero-title" name="hero_title" value="<?= Helpers::e($settings['hero_title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="settings-hero-subtitle">Subtítulo do banner</label>
        <textarea class="form-control" id="settings-hero-subtitle" name="hero_subtitle" rows="3"><?= Helpers::e($settings['hero_subtitle'] ?? '') ?></textarea>
      </div>
      <button class="btn" type="submit">Salvar banner</button>
    </form>

    <form id="hero-upload-form" method="post" action="/admin/configuracoes/hero" enctype="multipart/form-data" class="form-grid" style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--line)">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <div class="form-group">
        <label for="settings-hero-file">Trocar a foto da capa</label>
        <input class="form-control" id="settings-hero-file" type="file" name="hero_file" accept="image/jpeg,image/png,image/webp" required>
        <p class="muted" style="font-size:.82rem;margin:.35rem 0 0">Escolha a foto no computador ou celular. Melhor foto horizontal (paisagem), boa resolução. Ao escolher a foto, ela é enviada automaticamente e a capa muda na hora.</p>
      </div>
      <div class="admin-hero-preview" style="background-image:url('<?= Helpers::e($heroUrl) ?>')"></div>
      <button class="btn btn-outline" type="submit" id="hero-upload-btn">Trocar capa agora</button>
      <script>
      (function () {
        var f = document.getElementById('settings-hero-file');
        var form = document.getElementById('hero-upload-form');
        var btn = document.getElementById('hero-upload-btn');
        var prev = form.querySelector('.admin-hero-preview');
        f.addEventListener('change', function () {
          if (!f.files || !f.files[0]) return;
          prev.style.backgroundImage = 'url(' + URL.createObjectURL(f.files[0]) + ')';
          btn.disabled = true; btn.textContent = 'Enviando a foto...';
          form.submit();
        });
      })();
      </script>
    </form>
  </div>

  <div class="admin-card" id="colecoes">
    <h3>Coleções da home (3 cards com foto)</h3>
    <p class="muted" style="margin-top:0">Troque o título, o texto pequeno, a foto e o link de cada card. Para esconder um card, marque "Esconder".</p>
    <form method="post" action="/admin/configuracoes/colecoes" enctype="multipart/form-data" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <?php foreach ([1, 2, 3] as $n):
        $d = \App\Controllers\Admin\SettingsController::collectionTiles();
        $all = [];
        foreach ([1, 2, 3] as $i) { $all[$i] = null; }
        foreach ($d as $t) { $all[$t['n']] = $t; }
        $t = $all[$n];
        $isHidden = $t === null;
        if ($isHidden) {
            $t = ['small' => Helpers::setting("col{$n}_small", ''), 'title' => Helpers::setting("col{$n}_title", ''), 'image' => Helpers::setting("col{$n}_image", ''), 'link' => Helpers::setting("col{$n}_link", '')];
        }
      ?>
      <fieldset style="border:1px solid var(--line);border-radius:10px;padding:.9rem;margin:0">
        <legend style="padding:0 .4rem;font-weight:600">Card <?= $n ?></legend>
        <div class="admin-hero-preview" style="background-image:url('<?= Helpers::e((string) $t['image']) ?>');height:140px;background-position:center top"></div>
        <div class="form-group">
          <label>Trocar foto</label>
          <input class="form-control" type="file" name="col<?= $n ?>_file" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="form-group">
          <label>Título</label>
          <input class="form-control" name="col<?= $n ?>_title" value="<?= Helpers::e((string) $t['title']) ?>">
        </div>
        <div class="form-group">
          <label>Texto pequeno (acima do título)</label>
          <input class="form-control" name="col<?= $n ?>_small" value="<?= Helpers::e((string) $t['small']) ?>">
        </div>
        <div class="form-group">
          <label>Link ao clicar (ex.: /categoria/vestidos ou /catalogo)</label>
          <input class="form-control" name="col<?= $n ?>_link" value="<?= Helpers::e((string) $t['link']) ?>">
        </div>
        <label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="col<?= $n ?>_hidden" value="1" <?= $isHidden ? 'checked' : '' ?>> Esconder este card</label>
      </fieldset>
      <?php endforeach; ?>
      <button class="btn" type="submit">Salvar coleções</button>
    </form>
  </div>

  <div class="admin-card" id="galeria">
    <h3>Galeria de fotos da home</h3>
    <p class="muted" style="margin-top:0">Fotos que aparecem na home, abaixo do bloco do Instagram. Sem nenhuma foto, a seção some da home.</p>
    <?php $gallery = array_values(array_filter(json_decode((string) ($settings['instagram_feed'] ?? '[]'), true) ?: [], 'is_string')); ?>
    <?php if (!$gallery): ?><p class="muted"><strong>Nenhuma foto na galeria.</strong></p><?php endif; ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.7rem;margin-bottom:1rem">
      <?php foreach ($gallery as $g): ?>
        <form method="post" action="/admin/configuracoes/galeria" style="margin:0" onsubmit="return confirm('Excluir esta foto da home?');">
          <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
          <input type="hidden" name="action" value="remove">
          <input type="hidden" name="path" value="<?= Helpers::e($g) ?>">
          <div style="aspect-ratio:3/4;border-radius:8px;background:#eee url('<?= Helpers::e($g) ?>') center top/cover"></div>
          <button class="btn" style="width:100%;margin-top:.3rem;padding:.3rem;font-size:.8rem;background:#b3261e;border-color:#b3261e;color:#fff" type="submit">Excluir</button>
        </form>
      <?php endforeach; ?>
    </div>
    <?php if ($gallery): ?>
    <form method="post" action="/admin/configuracoes/galeria" style="margin:0 0 1rem" onsubmit="return confirm('Excluir TODAS as fotos da galeria da home?');">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <input type="hidden" name="action" value="clear">
      <button class="btn btn-outline" type="submit">Excluir todas as fotos</button>
    </form>
    <?php endif; ?>
    <form method="post" action="/admin/configuracoes/galeria" enctype="multipart/form-data" class="form-grid" style="border-top:1px solid var(--line);padding-top:1rem">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Adicionar fotos (pode escolher várias)</label>
        <input class="form-control" type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp" multiple required>
      </div>
      <button class="btn" type="submit">Adicionar à galeria</button>
    </form>
  </div>

  <div class="admin-card">
    <h3>Dados da loja</h3>
    <form method="post" action="/admin/configuracoes" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <?php
      $fields = [
        'store_name' => 'Nome da loja',
        'tagline' => 'Tagline',
        'whatsapp' => 'WhatsApp (DDI+DDD+número)',
        'instagram' => 'Instagram',
        'email' => 'E-mail de contato',
        'phone' => 'Telefone',
        'address' => 'Endereço',
        'og_image' => 'Imagem compartilhamento (URL)',
        'delivery_info' => 'Info de entrega',
        'exchange_policy' => 'Política de troca',
        'privacy_policy' => 'Privacidade',
      ];
      foreach ($fields as $key => $label): ?>
        <div class="form-group">
          <label for="store-<?= $key ?>"><?= Helpers::e($label) ?></label>
          <?php if (str_contains($key, 'policy') || $key === 'delivery_info'): ?>
            <textarea class="form-control" id="store-<?= $key ?>" name="<?= $key ?>" rows="3"><?= Helpers::e($settings[$key] ?? '') ?></textarea>
          <?php else: ?>
            <input class="form-control" id="store-<?= $key ?>" name="<?= $key ?>" value="<?= Helpers::e($settings[$key] ?? '') ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <button class="btn" type="submit">Salvar loja</button>
    </form>
  </div>
</div>

<div class="admin-card">
  <h3>Nova categoria</h3>
  <form method="post" action="/admin/configuracoes/categoria" class="form-grid two">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <div class="form-group"><label for="category-name">Nome</label><input class="form-control" id="category-name" name="name" required></div>
    <div class="form-group"><label for="category-image">Imagem URL</label><input class="form-control" id="category-image" name="image"></div>
    <div class="form-group"><label for="category-description">Descrição</label><input class="form-control" id="category-description" name="description"></div>
    <div class="form-group"><label for="category-sort">Ordem</label><input class="form-control" id="category-sort" type="number" name="sort_order" value="0"></div>
    <label><input type="checkbox" name="is_active" value="1" checked> Ativa</label>
    <button class="btn" type="submit">Salvar categoria</button>
  </form>
  <ul>
    <?php foreach ($categories as $c): ?>
      <li><?= Helpers::e($c['name']) ?> (<?= Helpers::e($c['slug']) ?>) <?= (int)$c['is_active'] ? '' : '[inativa]' ?></li>
    <?php endforeach; ?>
  </ul>
</div>

<div class="admin-card">
  <h3>Novo cupom</h3>
  <form method="post" action="/admin/configuracoes/cupom" class="form-grid two">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <div class="form-group"><label for="coupon-code">Código</label><input class="form-control" id="coupon-code" name="code" required></div>
    <div class="form-group"><label for="coupon-type">Tipo</label>
      <select class="form-control" id="coupon-type" name="type"><option value="percent">Percentual</option><option value="fixed">Fixo</option></select>
    </div>
    <div class="form-group"><label for="coupon-value">Valor</label><input class="form-control" id="coupon-value" name="value" inputmode="decimal" required></div>
    <div class="form-group"><label for="coupon-min">Mínimo</label><input class="form-control" id="coupon-min" name="min_subtotal" inputmode="decimal" value="0"></div>
    <div class="form-group"><label for="coupon-expires">Expira em</label><input class="form-control" id="coupon-expires" type="date" name="expires_at"></div>
    <label><input type="checkbox" name="is_active" value="1" checked> Ativo</label>
    <button class="btn" type="submit">Criar cupom</button>
  </form>
  <ul>
    <?php foreach ($coupons as $c): ?>
      <li style="margin-bottom:.5rem">
        <strong><?= Helpers::e($c['code']) ?></strong> — <?= $c['type'] === 'fixed' ? Helpers::money($c['value']) : Helpers::e((string)$c['value']) . '%' ?>
        <?= (int)$c['is_active'] ? '<span class="muted">(ativo)</span>' : '<span class="muted">(inativo)</span>' ?>
        <form method="post" action="/admin/configuracoes/cupom/<?= (int)$c['id'] ?>/alternar" style="display:inline">
          <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
          <button class="btn btn-outline" type="submit"><?= (int)$c['is_active'] ? 'Desativar' : 'Ativar' ?></button>
        </form>
        <form method="post" action="/admin/configuracoes/cupom/<?= (int)$c['id'] ?>/excluir" style="display:inline" onsubmit="return confirm('Excluir o cupom <?= Helpers::e($c['code']) ?>?')">
          <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
          <button class="btn btn-outline" type="submit">Excluir</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<div class="admin-card">
  <h3>Entrega e pagamento</h3>
  <?php foreach ($shipping as $s): ?><p><?= Helpers::e($s['name']) ?> — <?= Helpers::money($s['price']) ?></p><?php endforeach; ?>
  <?php foreach ($payments as $p): ?><p><?= Helpers::e($p['name']) ?> (<?= Helpers::e($p['code']) ?>)</p><?php endforeach; ?>
</div>

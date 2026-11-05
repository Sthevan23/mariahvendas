<?php use App\Core\Helpers;
$heroUrl = $settings['hero_image'] ?? '/uploads/moda/hero-atelier.jpg';
?>
<div class="admin-page-head">
  <h1 class="page-title">Configurações</h1>
</div>

<div class="admin-settings-grid">
  <div class="admin-card">
    <h3>Banner da home</h3>
    <p class="muted" style="margin-top:0">Título, texto e foto principal do site.</p>
    <form method="post" action="/admin/configuracoes" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <div class="form-group">
        <label>Título do banner</label>
        <input class="form-control" name="hero_title" value="<?= Helpers::e($settings['hero_title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Subtítulo do banner</label>
        <textarea class="form-control" name="hero_subtitle" rows="3"><?= Helpers::e($settings['hero_subtitle'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>URL da imagem (opcional se enviar arquivo abaixo)</label>
        <input class="form-control" name="hero_image" value="<?= Helpers::e($settings['hero_image'] ?? '') ?>">
      </div>
      <button class="btn" type="submit">Salvar banner</button>
    </form>

    <form method="post" action="/admin/configuracoes/hero" enctype="multipart/form-data" class="form-grid" style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid var(--line)">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <div class="form-group">
        <label>Enviar nova foto do banner</label>
        <input class="form-control" type="file" name="hero_file" accept="image/jpeg,image/png,image/webp" required>
        <p class="muted" style="font-size:.82rem;margin:.35rem 0 0">Recomendado: horizontal 16:9, boa resolução.</p>
      </div>
      <div class="admin-hero-preview" style="background-image:url('<?= Helpers::e($heroUrl) ?>')"></div>
      <button class="btn btn-outline" type="submit">Atualizar foto do banner</button>
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
          <label><?= Helpers::e($label) ?></label>
          <?php if (str_contains($key, 'policy') || $key === 'delivery_info'): ?>
            <textarea class="form-control" name="<?= $key ?>" rows="3"><?= Helpers::e($settings[$key] ?? '') ?></textarea>
          <?php else: ?>
            <input class="form-control" name="<?= $key ?>" value="<?= Helpers::e($settings[$key] ?? '') ?>">
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
    <div class="form-group"><label>Nome</label><input class="form-control" name="name" required></div>
    <div class="form-group"><label>Imagem URL</label><input class="form-control" name="image"></div>
    <div class="form-group"><label>Descrição</label><input class="form-control" name="description"></div>
    <div class="form-group"><label>Ordem</label><input class="form-control" type="number" name="sort_order" value="0"></div>
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
    <div class="form-group"><label>Código</label><input class="form-control" name="code" required></div>
    <div class="form-group"><label>Tipo</label>
      <select class="form-control" name="type"><option value="percent">Percentual</option><option value="fixed">Fixo</option></select>
    </div>
    <div class="form-group"><label>Valor</label><input class="form-control" name="value" required></div>
    <div class="form-group"><label>Mínimo</label><input class="form-control" name="min_subtotal" value="0"></div>
    <div class="form-group"><label>Expira em</label><input class="form-control" type="date" name="expires_at"></div>
    <label><input type="checkbox" name="is_active" value="1" checked> Ativo</label>
    <button class="btn" type="submit">Criar cupom</button>
  </form>
  <ul>
    <?php foreach ($coupons as $c): ?>
      <li><?= Helpers::e($c['code']) ?> — <?= Helpers::e($c['type']) ?> <?= Helpers::e((string)$c['value']) ?></li>
    <?php endforeach; ?>
  </ul>
</div>

<div class="admin-card">
  <h3>Entrega e pagamento</h3>
  <?php foreach ($shipping as $s): ?><p><?= Helpers::e($s['name']) ?> — <?= Helpers::money($s['price']) ?></p><?php endforeach; ?>
  <?php foreach ($payments as $p): ?><p><?= Helpers::e($p['name']) ?> (<?= Helpers::e($p['code']) ?>)</p><?php endforeach; ?>
</div>

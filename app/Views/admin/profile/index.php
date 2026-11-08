<?php use App\Core\Helpers; ?>
<div class="admin-page-head">
  <h1 class="page-title">Minha conta</h1>
</div>

<div class="admin-card">
  <h3>Dados do administrador</h3>
  <p><strong><?= Helpers::e($user['name'] ?? '') ?></strong></p>
  <p class="muted"><?= Helpers::e($user['email'] ?? '') ?></p>
  <?php if (!empty($user['phone'])): ?>
    <p class="muted"><?= Helpers::e($user['phone']) ?></p>
  <?php endif; ?>
</div>

<div class="admin-card">
  <h3>Alterar senha</h3>
  <form method="post" action="/admin/conta/senha" class="form-grid" style="max-width:420px">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <div class="form-group">
      <label for="profile-current-password">Senha atual</label>
      <input class="form-control" id="profile-current-password" type="password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="form-group">
      <label for="profile-new-password">Nova senha</label>
      <input class="form-control" id="profile-new-password" type="password" name="new_password"required minlength="8" autocomplete="new-password">
    </div>
    <div class="form-group">
      <label for="profile-new-password-confirm">Confirmar nova senha</label>
      <input class="form-control" id="profile-new-password-confirm" type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password">
    </div>
    <button class="btn" type="submit">Salvar nova senha</button>
  </form>
</div>

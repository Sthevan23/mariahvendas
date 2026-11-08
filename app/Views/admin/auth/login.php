<?php use App\Core\Helpers; ?>
<div class="admin-login-wrap">
  <div class="admin-login-card">
    <div class="admin-login-brand">
      <img src="/assets/brand/logo-header.png" alt="Ateliê Mariah">
      <h1>Painel administrativo</h1>
      <p>Gerencie produtos, pedidos e configurações da loja.</p>
    </div>
    <form method="post" action="/admin/login" class="form-grid">
      <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
      <div class="form-group">
        <label for="admin-login-email">E-mail</label>
        <input class="form-control" id="admin-login-email" type="email" name="email" required autocomplete="username" placeholder="admin@mariahvendas.com.br">
      </div>
      <div class="form-group">
        <label for="admin-login-password">Senha</label>
        <input class="form-control" id="admin-login-password" type="password" name="password" required autocomplete="current-password">
      </div>
      <button class="btn btn-glow" type="submit">Entrar no painel</button>
    </form>
    <p style="text-align:center;margin:1.25rem 0 0;font-size:.85rem">
      <a href="/">← Voltar para a loja</a>
    </p>
  </div>
</div>

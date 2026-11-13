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
        <input class="form-control" id="admin-login-email" type="email" name="email" required autocomplete="username" placeholder="seu@email.com">
      </div>
      <div class="form-group">
        <label for="admin-login-password">Senha</label>
        <div class="pw-wrap">
          <input class="form-control" id="admin-login-password" type="password" name="password" required autocomplete="current-password">
          <button type="button" class="pw-toggle" aria-label="Mostrar senha" aria-pressed="false" aria-controls="admin-login-password">
            <svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1M6.6 6.6C3.9 8.3 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
          </button>
        </div>
      </div>
      <label style="display:flex;align-items:center;gap:.5rem;font-size:.9rem;cursor:pointer">
        <input type="checkbox" name="remember" value="1" checked style="width:auto"> Manter conectado neste aparelho
      </label>
      <button class="btn btn-glow" type="submit">Entrar no painel</button>
    </form>
    <p style="text-align:center;margin:1.25rem 0 0;font-size:.85rem">
      <a href="/">← Voltar para a loja</a>
    </p>
  </div>
</div>
<script>
(function () {
  var btn = document.querySelector('.pw-toggle');
  if (!btn) return;
  var input = document.getElementById(btn.getAttribute('aria-controls'));
  btn.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    btn.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
  });
})();
</script>

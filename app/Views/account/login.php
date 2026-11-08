<?php use App\Core\Helpers; ?>
<div class="container" style="padding:3rem 0;max-width:480px">
  <h1 class="page-title">Entrar</h1>
  <form method="post" action="/login" class="form-grid" style="margin-top:1.5rem">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <div class="form-group"><label for="login-email">E-mail</label><input class="form-control" id="login-email" type="email" name="email" autocomplete="email" required></div>
    <div class="form-group"><label for="login-password">Senha</label><input class="form-control" id="login-password" type="password" name="password" autocomplete="current-password" required></div>
    <button class="btn" type="submit">Entrar</button>
  </form>
  <p class="muted" style="margin-top:1rem">Não tem conta? <a href="/cadastro">Cadastre-se</a></p>
</div>

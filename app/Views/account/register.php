<?php use App\Core\Helpers; ?>
<div class="container" style="padding:3rem 0;max-width:520px">
  <h1 class="page-title">Criar conta</h1>
  <form method="post" action="/cadastro" class="form-grid" style="margin-top:1.5rem">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <div class="form-group"><label for="register-name">Nome</label><input class="form-control" id="register-name" name="name" autocomplete="name" required></div>
    <div class="form-group"><label for="register-email">E-mail</label><input class="form-control" id="register-email" type="email" name="email" autocomplete="email" required></div>
    <div class="form-group"><label for="register-phone">Telefone</label><input class="form-control" id="register-phone" name="phone" type="tel" autocomplete="tel"></div>
    <div class="form-group"><label for="register-password">Senha</label><input class="form-control" id="register-password" type="password" name="password" minlength="6" autocomplete="new-password" required></div>
    <button class="btn" type="submit">Criar conta</button>
  </form>
</div>

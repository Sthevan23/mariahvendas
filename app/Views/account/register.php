<?php use App\Core\Helpers; ?>
<div class="container" style="padding:3rem 0;max-width:520px">
  <h1 class="page-title">Criar conta</h1>
  <form method="post" action="/cadastro" class="form-grid" style="margin-top:1.5rem">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf'] ?? '') ?>">
    <div class="form-group"><label>Nome</label><input class="form-control" name="name" required></div>
    <div class="form-group"><label>E-mail</label><input class="form-control" type="email" name="email" required></div>
    <div class="form-group"><label>Telefone</label><input class="form-control" name="phone"></div>
    <div class="form-group"><label>Senha</label><input class="form-control" type="password" name="password" minlength="6" required></div>
    <button class="btn" type="submit">Criar conta</button>
  </form>
</div>

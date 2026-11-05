<?php use App\Core\Helpers; if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32)); ?>
<div class="container" style="max-width:420px;padding:4rem 1rem">
  <h1 class="page-title">Admin Mariah Vendas</h1>
  <form method="post" action="/admin/login" class="admin-card form-grid" style="margin-top:1.5rem">
    <input type="hidden" name="_csrf" value="<?= Helpers::e($_SESSION['_csrf']) ?>">
    <div class="form-group"><label>E-mail</label><input class="form-control" type="email" name="email" required></div>
    <div class="form-group"><label>Senha</label><input class="form-control" type="password" name="password" required></div>
    <button class="btn" type="submit">Entrar</button>
  </form>
</div>

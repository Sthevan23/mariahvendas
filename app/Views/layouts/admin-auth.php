<?php use App\Core\Helpers; $flash = Helpers::flash(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin | Ateliê Mariah</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500&family=Outfit:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="admin-body">
<?php if ($flash): ?><div class="flash <?= Helpers::e($flash['type']) ?>"><?= Helpers::e($flash['message']) ?></div><?php endif; ?>
<?= $content ?>
</body>
</html>

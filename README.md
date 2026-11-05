# Mariah Vendas — E-commerce de moda (PHP)

Loja virtual: catálogo, carrinho, checkout, WhatsApp e painel admin.

## Rodar localmente

```bash
cd "c:\Users\Administrador\OneDrive\Desktop\Meus_clientes\mariah_altocostura"
php -S localhost:8080 -t public public/router.php
```

## Admin

Defina `ADMIN_EMAIL` e `ADMIN_PASSWORD` no `.env` (veja `.env.example`) e rode `php database/ensure_admin.php`.

## Cupons

- `MARIAH10` — 10% (mín. R$ 150)
- `BEMVINDA` — R$ 30 (mín. R$ 200)

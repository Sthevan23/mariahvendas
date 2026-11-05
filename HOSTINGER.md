# Mandalla — Deploy na Hostinger (PHP)

Loja PHP pura (MVC) + SQLite. Sem Node.js / Next.js.

## Login admin (troque depois de subir)

- URL: `https://SEU-DOMINIO/admin`
- E-mail: `admin@mandalla.com`
- Senha: `admin123`

## Passo a passo

### 1. Enviar os arquivos

No File Manager ou FTP, envie **toda a pasta do projeto** para `public_html` (ou a pasta do domínio), incluindo:

- `app/`, `config/`, `database/`, `public/`, `storage/`
- `bootstrap.php`, `routes.php`, `.htaccess`, `.env.example`

**Não envie:** `node_modules`, `.next`, `.git` (se houver).

### 2. Document root (importante)

No hPanel → **Domínios** → domínio → **Document Root** (ou Avançado):

Aponte para a pasta **`public`**:

```text
public_html/public
```

Se o painel não deixar mudar o document root, o `.htaccess` da raiz já redireciona o tráfego para `/public`.

### 3. Permissões de escrita

Pastas que precisam gravar arquivos (755 ou 775):

- `database/` — cria/atualiza o SQLite `mandalla.db`
- `public/uploads/` — fotos de produtos
- `storage/` — logs (se usar)

### 4. Configurar URL e WhatsApp

No File Manager, edite `.env.example` e salve como **`.env`** na raiz do projeto (ao lado de `bootstrap.php`):

```env
APP_URL=https://SEU-DOMINIO.com.br
APP_ENV=production
APP_DEBUG=false
WHATSAPP=5511999999999
```

Use só dígitos no WhatsApp (DDI + DDD + número).

Também dá para ajustar nome da loja, PIX e WhatsApp em **Admin → Configurações**.

### 5. PHP

hPanel → **Configuração PHP**:

- Versão **8.2+** (ideal 8.3)
- Extensões ativas: `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`

### 6. Catálogo Bali (fotos)

O projeto já vem com `database/mandalla.db` + fotos em `public/uploads/products/bali/`.

Ao subir os arquivos, a loja **atualiza sozinha** na primeira visita (troca o catálogo antigo pela Bali).

Se ainda aparecer foto antiga:
1. Confirme que subiu `public/uploads/products/bali/` e `database/mandalla.db`
2. Ou abra uma vez: `https://SEU-DOMINIO/atualizar-catalogo.php?key=mandalla-bali-2026`
3. Apague `public/atualizar-catalogo.php` depois
4. Ctrl+F5 na home

### 7. Testar

1. `https://SEU-DOMINIO/` — home da loja  
2. `https://SEU-DOMINIO/categoria/colecao-bali` — catálogo Bali  
3. `https://SEU-DOMINIO/admin` — painel  
4. Troque a senha do admin e o número do WhatsApp

Na primeira visita o banco SQLite é criado automaticamente (seed com Coleção Bali).

## Estrutura no servidor

```text
public_html/
  .htaccess          → manda o tráfego para public/
  bootstrap.php
  routes.php
  app/               → código PHP (bloqueado na web)
  config/
  database/          → mandalla.db (SQLite)
  public/            ← document root ideal
    index.php
    assets/
    uploads/
  storage/
```

## Problemas comuns

| Sintoma | O que checar |
|---------|----------------|
| 500 Internal Server Error | Versão do PHP; `pdo_sqlite`; permissão em `database/` |
| Página em branco | `APP_DEBUG=true` temporário no `.env` ou log de erros do hPanel |
| CSS/imagens não carregam | Document root deve ser `public/` (ou `.htaccess` da raiz ativo) |
| Upload de foto falha | Permissão em `public/uploads/` |
| Admin não entra | Rode de novo o seed apagando `database/mandalla.db` (cuidado: apaga pedidos) ou crie usuário no admin |

## Local (seu PC)

```bash
php -S localhost:8080 -t public public/router.php
```

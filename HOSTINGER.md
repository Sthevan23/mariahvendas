# Ateliê Mariah — Deploy na Hostinger (PHP + MySQL)

Loja PHP pura (MVC) com banco MySQL. Sem Node.js e sem SQLite.

## 1. Banco MySQL

1. No hPanel, abra **Bancos de dados MySQL** e anote o nome do banco, o usuário e a senha.
2. Abra o **phpMyAdmin**, selecione o banco **vazio** e vá em **Importar**.
3. Escolha o arquivo `hostinger_mysql.sql` e clique em **Executar**.

O arquivo cria as tabelas e carrega o catálogo, as configurações, os cupons e as formas de pagamento. Não traz usuários, pedidos, despesas nem reviews.

## 2. Enviar os arquivos

No File Manager ou FTP, envie **toda a pasta do projeto** para `public_html`, incluindo `app/`, `config/`, `database/`, `public/`, `storage/`, `bootstrap.php`, `routes.php` e `.htaccess`.

**Não envie:** `.git`, `node_modules`, `.env` local e `database/mariah_altocostura.db` (o banco local não é usado na Hostinger).

## 3. Document root

No hPanel → **Domínios** → **Document Root**, aponte para `public_html/public`.

Se o painel não deixar mudar, o `.htaccess` da raiz já redireciona o tráfego para `public/`.

## 4. Configurar o `.env`

Crie um arquivo `.env` na raiz do projeto (ao lado de `bootstrap.php`), baseado em `.env.example`:

```env
APP_URL=https://SEU-DOMINIO.com.br
APP_ENV=production
APP_DEBUG=false
WHATSAPP=5537998086607

DB_HOST=localhost
DB_NAME=nome_do_banco
DB_USER=usuario_do_banco
DB_PASS=senha_do_banco

ADMIN_EMAIL=seu-email@exemplo.com
ADMIN_PASSWORD=uma-senha-forte
```

- Use só dígitos no WhatsApp (DDI + DDD + número).
- `DB_HOST` costuma ser `localhost` na Hostinger. Confira no hPanel.
- **Nunca** envie o `.env` para o Git.

## 5. Criar o admin

Pelo terminal do hPanel, na pasta do projeto:

```bash
php database/ensure_admin.php
```

Depois entre em `https://SEU-DOMINIO/admin/login` com `ADMIN_EMAIL` e `ADMIN_PASSWORD`. Para trocar a senha depois, altere o `.env` e rode o comando de novo, ou use **Minha conta** no painel.

## 6. PHP

No hPanel → **Configuração PHP**:

- Versão **8.2+** (ideal 8.3)
- Extensões ativas: `pdo_mysql`, `mbstring`, `openssl`

## 7. Permissões de escrita

Pastas que precisam gravar arquivos (755 ou 775):

- `public/uploads/` — fotos de produtos e banner
- `storage/` — sessões e logs

## 8. Testar

1. `https://SEU-DOMINIO/` — home da loja
2. `https://SEU-DOMINIO/catalogo` — catálogo
3. `https://SEU-DOMINIO/admin/login` — painel
4. Troque a senha do admin e confirme o número do WhatsApp em **Admin → Configurações**

## Rodar localmente (seu PC)

O desenvolvimento local também usa MySQL. Opções:

- **XAMPP** ou **Laragon**: instale, crie um banco vazio, importe o `hostinger_mysql.sql` e preencha `DB_*` no `.env` local.
- Depois rode:

```bash
php -S localhost:8080 -t public public/router.php
```

## Problemas comuns

| Sintoma | O que checar |
|---------|--------------|
| Erro de conexão com o banco | `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` no `.env`; extensão `pdo_mysql` ativa |
| 500 Internal Server Error | Versão do PHP; permissão em `public/uploads/` e `storage/`; log de erros do hPanel |
| Página em branco | `APP_DEBUG=true` temporário no `.env` para ver o erro |
| CSS ou imagens não carregam | Document root deve ser `public/` (ou o `.htaccess` da raiz ativo) |
| Não consigo entrar no admin | Confira `ADMIN_EMAIL` e rode `php database/ensure_admin.php` de novo |
| Upload de foto falha | Permissão em `public/uploads/` |

## Estrutura no servidor

```text
public_html/
  .htaccess          → manda o tráfego para public/
  .env               → credenciais (não versionar)
  bootstrap.php
  routes.php
  app/               → código PHP (bloqueado na web)
  config/            → bloqueado na web
  database/          → scripts de manutenção e schema (bloqueado na web)
  public/            ← document root
    index.php
    assets/
    uploads/
  storage/           → sessões, logs e limite de login (bloqueado na web)
```

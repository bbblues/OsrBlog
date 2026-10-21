# Deploy — osr.blog.br (Inghost / hospedagem compartilhada)

Guia passo a passo para publicar o blog em hospedagem compartilhada (Inghost, cPanel) via FTP.

## Pré-requisitos

- Acesso ao **cPanel** e credenciais de **FTP**.
- Um **banco de dados MySQL** criado no cPanel (e um usuário com acesso a ele).
- PHP **8.4** habilitado no cPanel.
- (Opcional) **Acesso SSH** para rodar o Composer no servidor.

---

## 1. Criar o banco de dados no cPanel

1. No cPanel, acesse **MySQL Databases**.
2. Crie o banco (ex.: `osr_blog`) e um usuário (ex.: `osr`) com senha forte.
3. Associe o usuário ao banco com **ALL PRIVILEGES**.
4. Anote: `DB_NAME`, `DB_USER`, `DB_PASSWORD` e `DB_HOST` (geralmente `localhost`).

---

## 2. Definir o Document Root para a pasta `public/`

O **Document Root** (raiz do site) deve apontar para a pasta `public/` do projeto.

- No cPanel, em **Domains → seu domínio → Document Root**, aponte para a pasta `public/` do projeto.
- Isso garante que apenas `public/` fique acessível pela web; `src/`, `views/`, `storage/`, `config.php` e `.env` ficam **fora** da raiz pública.

> Se a hospedagem **não** permitir trocar o Document Root: coloque o conteúdo de `public/` em `public_html/` e o restante do projeto (`src/`, `vendor/`, `views/`, `storage/`, `config.php`, `composer.json`) em uma pasta **acima** de `public_html/` (ex.: `~/app/`). Nesse caso, ajuste os `dirname(__DIR__)` em `public/index.php` e `config.php` para apontar para essa pasta.

---

## 3. Enviar os arquivos via FTP

**NÃO envie:**

- `vendor/` — será gerado no servidor (passo 4).
- `.env` — contém segredos locais; crie um novo no servidor (passo 5).
- `.git/`, `.gitignore`, `.clinerules/`, `docker-compose.yml`, `Dockerfile`, `docker/`, `README.md`, `DEPLOY.md`.

**Envie:**

- `public/`, `src/`, `views/`, `storage/` (com `uploads/`, `logs/`, `backups/` vazios).
- `config.php`, `composer.json`, `composer.lock`, `database/schema.sql` (e, se quiser dados de teste, `database/seed.sql`).

---

## 4. Instalar as dependências (vendor/)

No servidor, na raiz do projeto, rode:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

Se **não houver SSH/Composer** no servidor:

- Gere o `vendor/` **localmente** (com PHP 8.4) e envie a pasta `vendor/` via FTP.

---

## 5. Criar o `.env` no servidor

Crie um arquivo `.env` na raiz do projeto (mesmo nível do `config.php`):

```
APP_ENV=production
APP_URL=https://osr.blog.br
APP_TIMEZONE=America/Sao_Paulo
DB_HOST=localhost
DB_PORT=3306
DB_NAME=osr_blog
DB_USER=osr
DB_PASSWORD=SUA_SENHA_FORTE
ANALYTICS_IP_SALT=UMA_STRING_ALEATORIA
POSTS_PER_PAGE=10
ADMIN_PER_PAGE=10
BACKUPS_TO_KEEP=5
```

---

## 6. Importar o schema (tabelas)

- **Via phpMyAdmin** (cPanel): selecione o banco → aba **Importar** → escolha `database/schema.sql` → **Executar**.
- **Via SSH/mysql**:
  ```bash
  mysql -u USUARIO -p BANCO < database/schema.sql
  ```

> Para dados de teste (admin e posts de exemplo), importe também `database/seed.sql`.

---

## 7. Permissões (checklist)

| Item | Permissão |
|---|---|
| Pastas (`src/`, `views/`, `public/`, `storage/`) | `755` |
| Arquivos (`*.php`, `*.json`, etc.) | `644` |
| `.env` | `600` |
| `storage/uploads/`, `storage/logs/`, `storage/backups/` | `755` (e **graváveis** pelo PHP) |

Via SSH (`chmod`):

```bash
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod 600 .env
chmod 755 storage storage/uploads storage/logs storage/backups
```

---

## 8. Testar

1. Acesse `https://osr.blog.br/` — deve carregar a home.
2. Acesse `https://osr.blog.br/admin/login` e entre com as credenciais do seed (`admin` / `admin123`, se usou o seed — **troque a senha**).
3. Teste um post, um comentário e o `sitemap.xml`.
4. Confira que `display_errors` está **off** (erros vão para o log, não para a tela).
5. Gere um backup em `/admin/backup` e teste o download.
6. Confirme o redirecionamento **HTTP → HTTPS** (acesse `http://osr.blog.br` e veja se vira `https://`).

---

## Checklist rápido

- [ ] Document Root aponta para `public/`.
- [ ] `vendor/` foi gerado no servidor (`composer install`).
- [ ] `.env` de produção criado (sem segredos locais).
- [ ] Banco importado (`schema.sql`).
- [ ] Permissões: `755` pastas, `644` arquivos, `600` `.env`.
- [ ] HTTPS ativo e redirecionamento HTTP→HTTPS funcionando.

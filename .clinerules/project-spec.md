# Especificação do Projeto — osr.blog.br

Este documento é a fonte da verdade para geração de código. Leia-o integralmente antes de qualquer implementação. Em caso de conflito, este documento prevalece.

## 1. Contexto
Blog pessoal sobre RPG analógico (movimento OSR). Um único autor. Painel administrativo restrito. Comentários anônimos com moderação posterior. Deploy via FTP em hospedagem compartilhada (Inghost). Desenvolvimento local em Docker no Windows.

## 2. Stack obrigatória
- PHP 8.4 puro (sem framework).
- MySQL 8.0.
- Composer (league/commonmark, vlucas/phpdotenv, cocur/slugify).
- Bootstrap 5 via CDN.
- Docker (php:8.4-apache + mysql:8.0).
- Sem Node.js, sem e-mail transacional, sem cron, sem cache.

## 3. Regras de comportamento do agente
1. Implemente apenas a fase solicitada. Não adiante código de fases futuras.
2. Nunca use mysqli, mysql_*, PDO::query() com interpolação, ou eval().
3. Sempre use prepared statements.
4. Sempre escape saída de usuário com htmlspecialchars($v, ENT_QUOTES, 'UTF-8').
5. Sempre valide entrada no servidor.
6. Comentários no código em português. Nomes de variáveis em inglês.
7. Sem dependências novas além das 3 listadas. Sem TODO silencioso.

## 4. Estrutura de pastas
- public/ (index.php, .htaccess, assets/)
- src/
  - Core/ (Router, Request, Response, Database, Session, Csrf, View)
  - Controllers/ (Públicos + Admin/)
  - Models/ (PostRepository, CategoryRepository, CommentRepository, UserRepository)
  - Support/ (Markdown, Slugger, Paginator, Analytics, Backup, ImageUpload)
  - Middleware/ (AuthMiddleware, CsrfMiddleware)
  - routes.php
- views/ (layout.php, partials/, public/, admin/)
- storage/ (uploads/, logs/, backups/)
- database/ (schema.sql, seed.sql)
- bin/ (test-db.php, generate-password.php)
- .env, .env.example, .gitignore, composer.json, config.php, docker-compose.yml, README.md

## 5. Convenções de código
- PSR-12 e PSR-4 (namespace raiz App\ mapeado para src/).
- Sem echo direto em controllers (usar View::render ou Response).
- Sem SQL em controllers (usar Models/Repositories).
- Todo POST redireciona após sucesso (padrão PRG) com flash message.

## 6. Modelo de dados (MySQL 8.0)
- utf8mb4 / utf8mb4_unicode_ci / InnoDB.
- Tabelas: users, categories, posts, post_category, comments, post_slug_history.

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    summary VARCHAR(500) NOT NULL,
    body_md MEDIUMTEXT NOT NULL,
    cover_image VARCHAR(255) NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_category (
    post_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, category_id),
    INDEX idx_category (category_id),
    CONSTRAINT fk_pc_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    author_name VARCHAR(80) NOT NULL,
    body TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'published',
    ip_hash CHAR(16) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_post_status (post_id, status, created_at),
    CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_slug_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    old_slug VARCHAR(200) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    INDEX idx_post (post_id),
    CONSTRAINT fk_slug_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

## 7. Rotas
Públicas: GET /, GET /post/{slug}, GET /categoria/{slug}, GET /busca, POST /post/{slug}/comentar, GET /sitemap.xml, GET /feed, GET /rss.xml, GET /robots.txt.
Admin: /admin/login, /admin/logout, /admin (dash), /admin/posts (CRUD), /admin/categorias (CRUD), /admin/comentarios (moderação), /admin/estatisticas, /admin/backup.

## 8. Regras funcionais
- Posts: published_at NULL = rascunho; <= NOW = publicado; > NOW = agendado. Home lista apenas publicados DESC.
- Slug: Gerado se vazio. Validação ^[a-z0-9]+(?:-[a-z0-9]+)*$. Unicidade com erro. Histórico 301 se mudar publicado.
- Paginação: ?page=N (nunca /page/N). 10 por página. ?page=1 -> 301. Página além do fim -> 404.
- Comentários: Anônimos. Sem links. Honeypot. CSRF. Rate limit 5/h. Publicação automática. Ordem ASC.
- Analytics: TSV mensal em storage/logs. IP hash SHA-256. Sem cookies.
- Backup: Zip com database.sql, uploads/, logs/, MANIFEST.txt. Retenção 5.

## 9. Segurança (não negociável)
- PDO com prepared statements.
- XSS: htmlspecialchars em toda saída.
- CSRF: token em todo POST.
- Sessão: HttpOnly, SameSite=Strict, regenerar ID no login.
- Senha: password_hash().
- Upload: validar finfo, renomear, bloquear PHP na pasta.
- Path traversal: realpath.
- Erros: display_errors=Off em prod.

## 10. Fases de implementação
- Fase 0: docker-compose.yml, composer.json, .env.example, .gitignore, config.php, public/index.php, estrutura de pastas. (Pronto quando localhost:8080 responder OK).
- Fase 1: schema.sql, seed.sql, src/Core/Database.php, bin/test-db.php. (Pronto quando o banco for populado e testado).
- Fase 2: Router, Session, Csrf, Views base.
- Fase 3: Área pública.
- Fase 4: Admin Auth + Posts.
- Fase 5: Admin Categorias + Comentários.
- Fase 6: SEO.
- Fase 7: Analytics e Backup.
- Fase 8: Hardening.

## 11. Critérios de qualidade
- php -l passa. Nenhuma interpolação em SQL. Saída com htmlspecialchars. POST com _csrf. Redirecionamento após POST.

## 12. .env (exemplo)
APP_ENV=local
APP_URL=http://localhost:8080
APP_TIMEZONE=America/Sao_Paulo
DB_HOST=mysql
DB_PORT=3306
DB_NAME=osr_blog
DB_USER=osr
DB_PASSWORD=changeme
ANALYTICS_IP_SALT=random_string
POSTS_PER_PAGE=10
ADMIN_PER_PAGE=10
BACKUPS_TO_KEEP=5
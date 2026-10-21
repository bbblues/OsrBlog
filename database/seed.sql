-- Dados iniciais para teste (Fase 1).
-- Pode ser executado mais de uma vez: remove os registros antes de inserir.

-- Remove registros existentes em ordem de dependência (filhos primeiro).
DELETE FROM post_slug_history;
DELETE FROM comments;
DELETE FROM post_category;
DELETE FROM posts;
DELETE FROM categories;
DELETE FROM users;

-- 1 usuário administrador (senha: admin123).
INSERT INTO users (id, username, password_hash, created_at) VALUES
    (1, 'admin', '$2y$10$GDxyi7qItbhzcm2DYUzPWe4lxze.3SyW7VIUc6o5OicPlT.O5C30K', NOW());

-- 3 categorias.
INSERT INTO categories (id, name, slug, created_at) VALUES
    (1, 'Aventura', 'aventura', NOW()),
    (2, 'Regras', 'regras', NOW()),
    (3, 'Resenhas', 'resenhas', NOW());

-- 3 posts: um publicado (passado), um rascunho (published_at NULL) e um agendado (futuro).
INSERT INTO posts (id, title, slug, summary, body_md, cover_image, published_at, created_at, updated_at) VALUES
    (1, 'Primeira Aventura no Mundo de OSR', 'primeira-aventura-no-mundo-de-osr', 'Relato de uma sessão de abertura em um sandbox OSR.', '# Primeira Aventura\n\nTexto do primeiro post publicado.', NULL, NOW() - INTERVAL 7 DAY, NOW(), NOW()),
    (2, 'Rascunho: Regras da Casa', 'rascunho-regras-da-casa', 'Notas ainda não publicadas sobre regras da mesa.', '# Regras da Casa\n\nRascunho em elaboração.', NULL, NULL, NOW(), NULL),
    (3, 'Resenha do D&D B/X', 'resenha-do-dd-bx', 'Resenha programada do clássico Basic/Expert.', '# Resenha\n\nTexto agendado para publicação futura.', NULL, NOW() + INTERVAL 3 DAY, NOW(), NULL);

-- Associa os posts às categorias.
INSERT INTO post_category (post_id, category_id) VALUES
    (1, 1),
    (2, 2),
    (3, 3);

<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOStatement;

/**
 * Acesso aos dados da tabela posts (e post_category para categorias).
 */
final class PostRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    // Lista posts publicados em ordem decrescente de publicação.
    public function findPublished(int $limit, int $offset): array
    {
        $sql = 'SELECT id, title, slug, summary, cover_image, published_at, created_at, updated_at
                FROM posts
                WHERE published_at IS NOT NULL AND published_at <= NOW()
                ORDER BY published_at DESC
                LIMIT :limit OFFSET :offset';

        return $this->withCategories($this->fetchAll($sql, [
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    public function countPublished(): int
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE published_at IS NOT NULL AND published_at <= NOW()';

        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    // Lista todos os posts publicados (para sitemap), sem paginação.
    public function findAllPublished(): array
    {
        $sql = 'SELECT id, title, slug, summary, published_at, updated_at
                FROM posts
                WHERE published_at IS NOT NULL AND published_at <= NOW()
                ORDER BY published_at DESC';

        return $this->fetchAll($sql, []);
    }

    // Busca um post pelo slug atual (qualquer status).
    public function findBySlug(string $slug): ?array
    {
        $sql = 'SELECT id, title, slug, summary, body_md, cover_image, published_at, created_at, updated_at
                FROM posts
                WHERE slug = :slug
                LIMIT 1';

        $post = $this->fetchOne($sql, ['slug' => $slug]);

        return $post === null ? null : $this->withCategories([$post])[0];
    }

    // Retorna o slug novo associado a um slug antigo (histórico), ou null.
    public function findNewSlugByOldSlug(string $oldSlug): ?string
    {
        $sql = 'SELECT p.slug
                FROM post_slug_history h
                INNER JOIN posts p ON p.id = h.post_id
                WHERE h.old_slug = :old_slug
                LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['old_slug' => $oldSlug]);

        $slug = $stmt->fetchColumn();

        return $slug === false ? null : (string) $slug;
    }

    // Lista posts publicados de uma categoria.
    public function findPublishedByCategory(string $categorySlug, int $limit, int $offset): array
    {
        $sql = 'SELECT p.id, p.title, p.slug, p.summary, p.cover_image, p.published_at, p.created_at, p.updated_at
                FROM posts p
                INNER JOIN post_category pc ON pc.post_id = p.id
                INNER JOIN categories c ON c.id = pc.category_id
                WHERE c.slug = :category_slug
                  AND p.published_at IS NOT NULL AND p.published_at <= NOW()
                ORDER BY p.published_at DESC
                LIMIT :limit OFFSET :offset';

        return $this->withCategories($this->fetchAll($sql, [
            'category_slug' => $categorySlug,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    public function countPublishedByCategory(string $categorySlug): int
    {
        $sql = 'SELECT COUNT(DISTINCT p.id)
                FROM posts p
                INNER JOIN post_category pc ON pc.post_id = p.id
                INNER JOIN categories c ON c.id = pc.category_id
                WHERE c.slug = :category_slug
                  AND p.published_at IS NOT NULL AND p.published_at <= NOW()';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['category_slug' => $categorySlug]);

        return (int) $stmt->fetchColumn();
    }

    // Busca posts publicados por termo (título, resumo ou corpo).
    public function searchPublished(string $query, int $limit, int $offset): array
    {
        $sql = 'SELECT id, title, slug, summary, cover_image, published_at, created_at, updated_at
                FROM posts
                WHERE published_at IS NOT NULL AND published_at <= NOW()
                  AND (title LIKE :like_title OR summary LIKE :like_summary OR body_md LIKE :like_body)
                ORDER BY published_at DESC
                LIMIT :limit OFFSET :offset';

        $like = '%' . $this->escapeLike($query) . '%';

        return $this->withCategories($this->fetchAll($sql, [
            'like_title' => $like,
            'like_summary' => $like,
            'like_body' => $like,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    public function countSearchPublished(string $query): int
    {
        $sql = 'SELECT COUNT(*)
                FROM posts
                WHERE published_at IS NOT NULL AND published_at <= NOW()
                  AND (title LIKE :like_title OR summary LIKE :like_summary OR body_md LIKE :like_body)';

        $like = '%' . $this->escapeLike($query) . '%';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'like_title' => $like,
            'like_summary' => $like,
            'like_body' => $like,
        ]);

        return (int) $stmt->fetchColumn();
    }

    // ------------------------------------------------------------------
    // Métodos administrativos (CRUD)
    // ------------------------------------------------------------------

    // Lista todos os posts (qualquer status), do mais recente ao mais antigo.
    public function findAll(int $limit, int $offset): array
    {
        $sql = 'SELECT id, title, slug, summary, cover_image, published_at, created_at, updated_at
                FROM posts
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset';

        return $this->withCategories($this->fetchAll($sql, [
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    // Lista id e título de todos os posts (para dropdowns de filtro).
    public function findAllTitles(): array
    {
        return $this->pdo->query('SELECT id, title FROM posts ORDER BY id DESC')->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    }

    public function countDrafts(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM posts WHERE published_at IS NULL')->fetchColumn();
    }

    public function countScheduled(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM posts WHERE published_at IS NOT NULL AND published_at > NOW()')->fetchColumn();
    }

    // Busca um post pelo id (com categorias).
    public function findById(int $id): ?array
    {
        $sql = 'SELECT id, title, slug, summary, body_md, cover_image, published_at, created_at, updated_at
                FROM posts
                WHERE id = :id
                LIMIT 1';

        $post = $this->fetchOne($sql, ['id' => $id]);

        return $post === null ? null : $this->withCategories([$post])[0];
    }

    // Cria um post e associa as categorias; retorna o id gerado.
    public function create(array $data, array $categoryIds): int
    {
        $sql = 'INSERT INTO posts (title, slug, summary, body_md, cover_image, published_at, created_at, updated_at)
                VALUES (:title, :slug, :summary, :body_md, :cover_image, :published_at, NOW(), NULL)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'summary' => $data['summary'],
            'body_md' => $data['body_md'],
            'cover_image' => $data['cover_image'],
            'published_at' => $data['published_at'],
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $this->syncCategories($id, $categoryIds);

        return $id;
    }

    // Atualiza um post e sincroniza as categorias.
    public function update(int $id, array $data, array $categoryIds): void
    {
        $sql = 'UPDATE posts
                SET title = :title, slug = :slug, summary = :summary, body_md = :body_md,
                    cover_image = :cover_image, published_at = :published_at, updated_at = NOW()
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'summary' => $data['summary'],
            'body_md' => $data['body_md'],
            'cover_image' => $data['cover_image'],
            'published_at' => $data['published_at'],
            'id' => $id,
        ]);

        $this->syncCategories($id, $categoryIds);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Sincroniza as categorias de um post (remove e reinsere).
    public function syncCategories(int $postId, array $categoryIds): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM post_category WHERE post_id = :post_id');
        $stmt->execute(['post_id' => $postId]);

        if ($categoryIds === []) {
            return;
        }

        $sql = 'INSERT INTO post_category (post_id, category_id) VALUES (:post_id, :category_id)';
        $stmt = $this->pdo->prepare($sql);
        foreach ($categoryIds as $categoryId) {
            $stmt->execute(['post_id' => $postId, 'category_id' => $categoryId]);
        }
    }

    // Registra um slug antigo no histórico (para redirecionamento 301).
    public function saveSlugHistory(int $postId, string $oldSlug): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO post_slug_history (post_id, old_slug, created_at) VALUES (:post_id, :old_slug, NOW())');
        $stmt->execute(['post_id' => $postId, 'old_slug' => $oldSlug]);
    }

    // ------------------------------------------------------------------
    // Traduções (post_translations)
    // ------------------------------------------------------------------

    // Insere uma tradução para um post. $data deve conter: language, title, slug, summary, body_md.
    public function saveTranslation(int $postId, array $data): void
    {
        $sql = 'INSERT INTO post_translations (post_id, language, title, slug, summary, body_md, created_at)
                VALUES (:post_id, :language, :title, :slug, :summary, :body_md, NOW())';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'post_id' => $postId,
            'language' => $data['language'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'summary' => $data['summary'],
            'body_md' => $data['body_md'],
        ]);
    }

    // Atualiza uma tradução existente de um post. $data deve conter: title, slug, summary, body_md.
    public function updateTranslation(int $postId, string $lang, array $data): void
    {
        $sql = 'UPDATE post_translations
                SET title = :title, slug = :slug, summary = :summary, body_md = :body_md
                WHERE post_id = :post_id AND language = :language';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'summary' => $data['summary'],
            'body_md' => $data['body_md'],
            'post_id' => $postId,
            'language' => $lang,
        ]);
    }

    // Busca a tradução de um post por idioma.
    public function findTranslationByPostId(int $postId, string $lang): ?array
    {
        $sql = 'SELECT id, post_id, language, title, slug, summary, body_md, created_at
                FROM post_translations
                WHERE post_id = :post_id AND language = :language
                LIMIT 1';

        return $this->fetchOne($sql, ['post_id' => $postId, 'language' => $lang]);
    }

    // Busca uma tradução pelo slug (para rota pública em inglês).
    public function findTranslationBySlug(string $slug): ?array
    {
        $sql = 'SELECT id, post_id, language, title, slug, summary, body_md, created_at
                FROM post_translations
                WHERE slug = :slug
                LIMIT 1';

        return $this->fetchOne($sql, ['slug' => $slug]);
    }

    // ------------------------------------------------------------------
    // Helpers internos
    // ------------------------------------------------------------------

    private function fetchAll(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function fetchOne(string $sql, array $params): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, $params);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    // Vincula parâmetros, usando PARAM_INT para inteiros (LIMIT/OFFSET).
    private function bind(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
    }

    // Anexa as categorias a cada post (consulta única, evita N+1).
    private function withCategories(array $posts): array
    {
        if ($posts === []) {
            return $posts;
        }

        $ids = array_values(array_map('intval', array_column($posts, 'id')));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = 'SELECT pc.post_id, c.id, c.name, c.slug
                FROM post_category pc
                INNER JOIN categories c ON c.id = pc.category_id
                WHERE pc.post_id IN (' . $placeholders . ')
                ORDER BY c.name ASC';

        $stmt = $this->pdo->prepare($sql);
        foreach ($ids as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $byPost = [];
        foreach ($stmt->fetchAll() as $row) {
            $byPost[(int) $row['post_id']][] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
            ];
        }

        foreach ($posts as &$post) {
            $post['categories'] = $byPost[(int) $post['id']] ?? [];
        }
        unset($post);

        return $posts;
    }

    // Escapa os curingas do LIKE para evitar busca fora do esperado.
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

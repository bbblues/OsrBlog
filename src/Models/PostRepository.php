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

<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Acesso aos dados da tabela categories.
 */
final class CategoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    // Lista todas as categorias em ordem alfabética.
    public function findAll(): array
    {
        $sql = 'SELECT id, name, slug, created_at FROM categories ORDER BY name ASC';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = 'SELECT id, name, slug, created_at FROM categories WHERE slug = :slug LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    // ------------------------------------------------------------------
    // Admin (CRUD)
    // ------------------------------------------------------------------

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT id, name, slug, created_at FROM categories WHERE id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO categories (name, slug, created_at) VALUES (:name, :slug, NOW())';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $data['name'], 'slug' => $data['slug']]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE categories SET name = :name, slug = :slug WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $data['name'], 'slug' => $data['slug'], 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Verifica se um slug já existe em outra categoria.
    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
        $params = ['slug' => $slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $ignoreId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }
}

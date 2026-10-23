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

    // Lista todas as categorias em ordem alfabética (nome localizado pelo idioma).
    public function findAll(string $lang = 'pt'): array
    {
        $sql = 'SELECT id, name, name_en, slug, created_at FROM categories ORDER BY name ASC';

        return array_map(
            fn (array $row): array => $this->localize($row, $lang),
            $this->pdo->query($sql)->fetchAll()
        );
    }

    public function findBySlug(string $slug, string $lang = 'pt'): ?array
    {
        $sql = 'SELECT id, name, name_en, slug, created_at FROM categories WHERE slug = :slug LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);

        $row = $stmt->fetch();

        return $row === false ? null : $this->localize($row, $lang);
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
        $sql = 'SELECT id, name, name_en, slug, created_at FROM categories WHERE id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO categories (name, name_en, slug, created_at) VALUES (:name, :name_en, :slug, NOW())';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $data['name'], 'name_en' => $data['name_en'], 'slug' => $data['slug']]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE categories SET name = :name, name_en = :name_en, slug = :slug WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $data['name'], 'name_en' => $data['name_en'], 'slug' => $data['slug'], 'id' => $id]);
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

    // Substitui o nome PT pelo nome EN quando o idioma é inglês e existe tradução.
    private function localize(array $row, string $lang): array
    {
        if ($lang === 'en' && isset($row['name_en']) && $row['name_en'] !== null && trim((string) $row['name_en']) !== '') {
            $row['name'] = (string) $row['name_en'];
        }

        return $row;
    }
}

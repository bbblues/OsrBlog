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
}

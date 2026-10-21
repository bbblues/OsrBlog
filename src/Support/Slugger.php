<?php

declare(strict_types=1);

namespace App\Support;

use Cocur\Slugify\Slugify;
use PDO;

/**
 * Gera e valida a unicidade de slugs usando cocur/slugify.
 */
final class Slugger
{
    public function __construct(private PDO $pdo)
    {
    }

    // Gera um slug a partir de um texto (minúsculas, números e hífens).
    public function slugify(string $text): string
    {
        return (string) (new Slugify())->slugify($text);
    }

    // Verifica se um slug já existe em outro post.
    public function exists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE slug = :slug';
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

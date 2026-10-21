<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Acesso aos dados da tabela users.
 */
final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $sql = 'SELECT id, username, password_hash, created_at FROM users WHERE username = :username LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['username' => $username]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}

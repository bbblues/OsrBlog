<?php

declare(strict_types=1);

// Script CLI de teste da conexão com o banco de dados (Fase 1).
// Uso: docker compose exec app php bin/test-db.php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.php';

use App\Core\Database;

try {
    $pdo = Database::getInstance()->getPdo();

    // Contagem de registros em tabelas fixas (SQL literal, sem interpolação).
    $postsCount = (int) $pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $usersCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $categoriesCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

    echo 'Conexão com o banco de dados: OK' . PHP_EOL;
    echo 'posts: ' . $postsCount . PHP_EOL;
    echo 'users: ' . $usersCount . PHP_EOL;
    echo 'categories: ' . $categoriesCount . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

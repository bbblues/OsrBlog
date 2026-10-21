<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Conexão única (Singleton) com o banco de dados MySQL via PDO.
 */
final class Database
{
    private static ?self $instance = null;

    private PDO $pdo;

    // Construtor privado: a instância só pode ser obtida via getInstance().
    private function __construct()
    {
        $this->pdo = $this->createPdo();
    }

    // Impede a clonagem da instância.
    private function __clone()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // Cria a conexão PDO usando as constantes globais definidas no config.php.
    private function createPdo(): PDO
    {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        return new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}

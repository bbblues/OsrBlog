<?php

declare(strict_types=1);

use Dotenv\Dotenv;

// Carrega o autoloader do Composer.
require_once __DIR__ . '/vendor/autoload.php';

// Carrega as variáveis de ambiente do arquivo .env (não versionado).
Dotenv::createImmutable(__DIR__)->safeLoad();

// Define o fuso horário padrão da aplicação.
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');

// Controla a exibição de erros conforme o ambiente.
$appEnv = $_ENV['APP_ENV'] ?? 'production';
if ($appEnv === 'local') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

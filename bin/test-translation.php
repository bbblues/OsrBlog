<?php

declare(strict_types=1);

// Script CLI de teste da tradução automática via DeepSeek.
// Uso: docker compose exec app php bin/test-translation.php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.php';

use App\Support\Translator;

$translator = new Translator();

$result = $translator->translatePost(
    'Primeira Aventura',
    'Um teste',
    '## Título'
);

if ($result === null) {
    fwrite(STDERR, 'Falha ao traduzir. Verifique DEEPSEEK_API_KEY no .env e a conectividade.' . PHP_EOL);
    exit(1);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

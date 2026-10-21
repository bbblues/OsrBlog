<?php

declare(strict_types=1);

// Ponto de entrada único da aplicação (front controller).
require_once dirname(__DIR__) . '/config.php';

// Resposta mínima da Fase 0: confirma que a aplicação está no ar.
header('Content-Type: text/plain; charset=UTF-8');
echo 'OK';

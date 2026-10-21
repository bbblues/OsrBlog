<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

// Front controller: todas as requisições HTTP entram por aqui.
require_once dirname(__DIR__) . '/config.php';

// Inicia a sessão com cookies seguros (HttpOnly + SameSite=Strict).
$session = new Session();
$session->start();

// Infraestrutura de segurança.
$csrf = new Csrf($session);
$authMiddleware = new AuthMiddleware($session);
$csrfMiddleware = new CsrfMiddleware($csrf);

// Roteador: valida CSRF globalmente em todo POST e carrega as rotas.
$router = new Router();
$router->addGlobalMiddleware([$csrfMiddleware, 'handle']);

$routes = require dirname(__DIR__) . '/src/routes.php';
$routes($router, $authMiddleware);

// Despacha a requisição e envia a resposta ao cliente.
$request = Request::fromGlobals();

try {
    $response = $router->dispatch($request);
} catch (Throwable $e) {
    $message = ($_ENV['APP_ENV'] ?? 'production') === 'local'
        ? $e->getMessage()
        : 'Erro interno do servidor.';
    $response = new Response($message, 500);
}

$response->send();


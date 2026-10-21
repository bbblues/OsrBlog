<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

/**
 * Valida o token CSRF em todas as requisições POST.
 */
final class CsrfMiddleware
{
    public function __construct(private Csrf $csrf)
    {
    }

    public function handle(Request $request): ?Response
    {
        // CSRF só se aplica a requisições que alteram estado (POST).
        if (!$request->isMethod('POST')) {
            return null;
        }

        $token = $request->post('_csrf');
        $token = is_string($token) ? $token : null;

        if ($this->csrf->validate($token)) {
            return null;
        }

        return new Response('Falha na validação CSRF (token ausente ou inválido). Recarregue a página e tente novamente.', 403);
    }
}

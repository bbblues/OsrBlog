<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Verifica se o usuário está autenticado antes de acessar rotas administrativas.
 */
final class AuthMiddleware
{
    // Chave de sessão que indica o usuário logado (definida na Fase 4).
    private const AUTH_KEY = 'user_id';

    public function __construct(private Session $session)
    {
    }

    public function handle(Request $request): ?Response
    {
        if ($this->session->has(self::AUTH_KEY)) {
            return null;
        }

        // Usuário não autenticado: redireciona para a tela de login.
        return Response::redirect('/admin/login');
    }
}

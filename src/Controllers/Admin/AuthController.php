<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\UserRepository;

/**
 * Autenticação do painel administrativo.
 */
final class AuthController
{
    public function __construct(
        private UserRepository $users,
        private Session $session,
        private Csrf $csrf
    ) {
    }

    // Exibe o formulário de login.
    public function form(Request $request): Response
    {
        return View::render('admin/login', [
            'title' => 'Login',
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }

    // Processa o login.
    public function login(Request $request): Response
    {
        $username = trim((string) $request->post('username', ''));
        $password = (string) $request->post('password', '');

        $user = $this->users->findByUsername($username);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $this->session->flash('error', 'Usuário ou senha inválidos.');

            return Response::redirect('/admin/login');
        }

        // Regenera o ID da sessão para evitar fixação de sessão.
        $this->session->regenerate();
        $this->session->set('user_id', (int) $user['id']);

        $this->session->flash('success', 'Login realizado com sucesso.');

        return Response::redirect('/admin');
    }

    // Encerra a sessão.
    public function logout(Request $request): Response
    {
        $this->session->destroy();

        return Response::redirect('/admin/login');
    }
}

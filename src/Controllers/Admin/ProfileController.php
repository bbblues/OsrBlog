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
 * Perfil do administrador: alteração da própria senha.
 */
final class ProfileController
{
    public function __construct(
        private UserRepository $users,
        private Session $session,
        private Csrf $csrf
    ) {
    }

    // Exibe o formulário de alteração de senha.
    public function form(Request $request): Response
    {
        return View::render('admin/profile/form', [
            'title' => 'Meu Perfil',
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }

    // Processa a troca de senha.
    public function update(Request $request): Response
    {
        $userId = $this->session->get('user_id');

        // Defensivo: a rota já passa pelo AuthMiddleware, mas valida o ID mesmo assim.
        if (!is_int($userId) && !is_string($userId)) {
            $this->session->flash('error', 'Sessão expirada. Faça login novamente.');

            return Response::redirect('/admin/login');
        }

        $userId = (int) $userId;
        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->session->flash('error', 'Usuário não encontrado.');

            return Response::redirect('/admin/perfil');
        }

        $currentPassword = (string) $request->post('current_password', '');
        $newPassword = (string) $request->post('new_password', '');
        $confirmPassword = (string) $request->post('confirm_password', '');

        // Verifica se a senha atual informada confere com o hash armazenado.
        if (!password_verify($currentPassword, (string) $user['password_hash'])) {
            $this->session->flash('error', 'Senha atual incorreta.');

            return Response::redirect('/admin/perfil');
        }

        // Validação da nova senha no servidor.
        if ($newPassword === '') {
            $this->session->flash('error', 'A nova senha é obrigatória.');

            return Response::redirect('/admin/perfil');
        }

        if (mb_strlen($newPassword) < 8) {
            $this->session->flash('error', 'A nova senha deve ter pelo menos 8 caracteres.');

            return Response::redirect('/admin/perfil');
        }

        if ($newPassword !== $confirmPassword) {
            $this->session->flash('error', 'A confirmação da nova senha não confere.');

            return Response::redirect('/admin/perfil');
        }

        // Evita reutilizar a senha atual como nova senha.
        if (password_verify($newPassword, (string) $user['password_hash'])) {
            $this->session->flash('error', 'A nova senha deve ser diferente da senha atual.');

            return Response::redirect('/admin/perfil');
        }

        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        $this->session->flash('success', 'Senha alterada com sucesso.');

        return Response::redirect('/admin/perfil');
    }
}

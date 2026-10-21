<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gera e valida tokens CSRF armazenados na sessão.
 */
final class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public function __construct(private Session $session)
    {
    }

    // Retorna o token atual, gerando um novo se necessário.
    public function token(): string
    {
        if (!$this->session->has(self::TOKEN_KEY)) {
            $this->session->set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }

        return (string) $this->session->get(self::TOKEN_KEY);
    }

    // Valida o token recebido contra o armazenado (comparação em tempo constante).
    public function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $stored = $this->session->get(self::TOKEN_KEY);
        if (!is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    // Gera o campo oculto para formulários.
    public function field(): string
    {
        $token = $this->token();

        return '<input type="hidden" name="_csrf" value="'
            . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gerencia a sessão com cookies seguros (HttpOnly e SameSite=Strict).
 */
final class Session
{
    private bool $started = false;

    // Inicia a sessão com configurações seguras de cookie.
    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;

            return;
        }

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        session_start();
        $this->started = true;
    }

    // Regenera o ID da sessão (uso após o login).
    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    // Destrói a sessão e remove o cookie.
    public function destroy(): void
    {
        $this->start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Strict',
            ]);
        }

        session_destroy();
        $this->started = false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    // Define (2 argumentos) ou consome (1 argumento) uma mensagem flash.
    public function flash(string $key, mixed $value = null): mixed
    {
        if (func_num_args() === 2) {
            $this->set('_flash_' . $key, $value);

            return null;
        }

        $value = $this->get('_flash_' . $key);
        $this->remove('_flash_' . $key);

        return $value;
    }
}

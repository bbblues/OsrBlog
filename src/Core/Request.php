<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Encapsula os dados da requisição HTTP atual.
 */
final class Request
{
    // Idioma atual da requisição (definido pelo Router: 'pt' ou 'en').
    private string $lang = 'pt';

    /**
     * @param array<string, mixed> $query Parâmetros da query string (GET).
     * @param array<string, mixed> $post  Dados enviados no corpo (POST).
     */
    public function __construct(
        private string $method,
        private string $uri,
        private array $query = [],
        private array $post = []
    ) {
    }

    // Cria a requisição a partir das variáveis superglobais.
    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = self::extractPath($_SERVER['REQUEST_URI'] ?? '/');

        return new self($method, $uri, $_GET, $_POST);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    // Retorna um parâmetro da query string (GET).
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    // Retorna um dado do corpo (POST).
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function allQuery(): array
    {
        return $this->query;
    }

    public function allPost(): array
    {
        return $this->post;
    }

    // Idioma atual da requisição ('pt' ou 'en').
    public function lang(): string
    {
        return $this->lang;
    }

    // Define o idioma da requisição (chamado pelo Router).
    public function setLang(string $lang): void
    {
        $this->lang = $lang === 'en' ? 'en' : 'pt';
    }

    // Retorna um cabeçalho HTTP da requisição (ex.: 'Accept-Language'), ou null.
    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $_SERVER[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    // Extrai apenas o caminho (sem query string) da URI.
    private static function extractPath(string $requestUri): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? $path : '/';
    }
}

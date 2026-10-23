<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Roteador simples: registra rotas no formato "MÉTODO /caminho/{param}".
 */
final class Router
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $routes = [];

    /**
     * @var array<int, callable>
     */
    private array $globalMiddleware = [];

    public function add(string $method, string $pattern, callable $handler, callable ...$middleware): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => $this->compilePattern($pattern),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $pattern, callable $handler, callable ...$middleware): void
    {
        $this->add('GET', $pattern, $handler, ...$middleware);
    }

    public function post(string $pattern, callable $handler, callable ...$middleware): void
    {
        $this->add('POST', $pattern, $handler, ...$middleware);
    }

    // Registra um middleware executado antes de todas as rotas.
    public function addGlobalMiddleware(callable $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    // Despacha a requisição para a rota correspondente.
    public function dispatch(Request $request): Response
    {
        // Detecta o idioma a partir do prefixo da URL (/en/ -> 'en'; caso contrário 'pt').
        $request->setLang($this->detectLanguage($request->uri()));

        $route = $this->match($request);

        if ($route === null) {
            return Response::notFound();
        }

        // Middleware global (ex.: validação de CSRF em POST).
        foreach ($this->globalMiddleware as $middleware) {
            $response = $middleware($request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        // Middleware específico da rota (ex.: autenticação no admin).
        foreach ($route['middleware'] as $middleware) {
            $response = $middleware($request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        $result = ($route['handler'])($request, ...$route['params']);

        if ($result instanceof Response) {
            return $result;
        }

        return new Response((string) $result);
    }

    // Encontra a rota que casa com o método e a URI da requisição.
    private function match(Request $request): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (preg_match($route['regex'], $this->stripLanguagePrefix($request->uri()), $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            $route['params'] = $params;

            return $route;
        }

        return null;
    }

    // Detecta o idioma a partir da URI: '/en' ou '/en/...' -> 'en'; caso contrário 'pt'.
    private function detectLanguage(string $uri): string
    {
        return ($uri === '/en' || str_starts_with($uri, '/en/')) ? 'en' : 'pt';
    }

    // Remove o prefixo '/en' da URI para casar com os padrões de rota sem prefixo.
    private function stripLanguagePrefix(string $uri): string
    {
        if ($uri === '/en') {
            return '/';
        }

        if (str_starts_with($uri, '/en/')) {
            $stripped = substr($uri, 3);

            return $stripped === '' ? '/' : $stripped;
        }

        return $uri;
    }

    // Converte "/post/{slug}" em uma expressão regular com grupo nomeado.
    private function compilePattern(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);

        return '#^' . $regex . '$#';
    }
}

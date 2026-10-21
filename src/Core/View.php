<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renderiza arquivos da pasta views/ usando extract() para expor os dados.
 */
final class View
{
    public function __construct(private string $viewsPath)
    {
    }

    // Cria uma instância apontando para a pasta views/ padrão do projeto.
    public static function make(): self
    {
        return new self(dirname(__DIR__, 2) . '/views');
    }

    // Renderiza um template com os dados fornecidos e retorna uma Response.
    public static function render(string $template, array $data = []): Response
    {
        return self::make()->response($template, $data);
    }

    // Renderiza o template e devolve o HTML dentro de uma Response.
    public function response(string $template, array $data = []): Response
    {
        return new Response($this->renderTemplate($template, $data));
    }

    private function renderTemplate(string $template, array $data): string
    {
        $file = rtrim($this->viewsPath, '/\\') . DIRECTORY_SEPARATOR . $template;

        if (!is_file($file)) {
            throw new \RuntimeException('View não encontrada: ' . $template);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $file;

        return (string) ob_get_clean();
    }
}

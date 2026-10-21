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

    // Renderiza o template de conteúdo e o envolve no layout base.
    public static function render(string $template, array $data = []): Response
    {
        $view = self::make();
        $content = $view->renderTemplate($template, $data);
        $html = $view->renderTemplate('layout', array_merge($data, ['content' => $content]));

        return new Response($html);
    }

    private function renderTemplate(string $template, array $data): string
    {
        // Aceita templates com ou sem a extensão .php.
        if (!str_ends_with($template, '.php')) {
            $template .= '.php';
        }

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

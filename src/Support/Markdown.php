<?php

declare(strict_types=1);

namespace App\Support;

use League\CommonMark\CommonMarkConverter;

/**
 * Converte Markdown em HTML seguro usando League CommonMark.
 */
final class Markdown
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            // Escapa HTML cru embutido no Markdown para evitar XSS.
            'html_input' => 'escape',
            // Bloqueia links perigosos (ex.: javascript:).
            'allow_unsafe_links' => false,
        ]);
    }

    public function toHtml(string $markdown): string
    {
        return (string) $this->converter->convert($markdown);
    }
}

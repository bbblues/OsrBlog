<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Retorna o arquivo robots.txt apontando para o sitemap.
 */
final class RobotsController
{
    public function index(Request $request): Response
    {
        $appUrl = rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost:8080'), '/');

        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin\n";
        $content .= "\n";
        $content .= 'Sitemap: ' . $appUrl . "/sitemap.xml\n";

        return new Response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\PostRepository;

/**
 * Gera o feed RSS 2.0 com os últimos 20 posts publicados.
 */
final class FeedController
{
    public function __construct(private PostRepository $posts)
    {
    }

    public function index(Request $request): Response
    {
        $appUrl = rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost:8080'), '/');

        $posts = $this->posts->findPublished(20, 0);

        return new Response($this->renderRss($posts, $appUrl), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    private function renderRss(array $posts, string $appUrl): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<rss version=\"2.0\">\n";
        $xml .= "  <channel>\n";
        $xml .= "    <title>osr.blog.br</title>\n";
        $xml .= '    <link>' . htmlspecialchars($appUrl . '/', ENT_QUOTES, 'UTF-8') . "</link>\n";
        $xml .= "    <description>Blog pessoal sobre RPG analógico (movimento OSR).</description>\n";

        foreach ($posts as $post) {
            $link = $appUrl . '/post/' . $post['slug'];

            $xml .= "    <item>\n";
            $xml .= '      <title>' . htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') . "</title>\n";
            $xml .= '      <link>' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "</link>\n";
            $xml .= '      <guid>' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "</guid>\n";
            $xml .= '      <description>' . htmlspecialchars((string) $post['summary'], ENT_QUOTES, 'UTF-8') . "</description>\n";
            $xml .= '      <pubDate>' . $this->pubDate((string) $post['published_at']) . "</pubDate>\n";
            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= "</rss>\n";

        return $xml;
    }

    // Converte o DATETIME (armazenado em UTC) para o formato RFC 822.
    private function pubDate(string $datetime): string
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime, new \DateTimeZone('UTC'));

        if ($date === false) {
            return '';
        }

        return $date->format('D, d M Y H:i:s O');
    }
}

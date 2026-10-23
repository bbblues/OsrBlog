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

        $items = $this->buildItems($this->posts->findPublished(20, 0, 'pt'), 'pt', $appUrl);
        $items = array_merge($items, $this->buildItems($this->posts->findPublished(20, 0, 'en'), 'en', $appUrl));

        usort($items, static fn (array $a, array $b): int => $b['ts'] <=> $a['ts']);
        $items = array_slice($items, 0, 20);

        return new Response($this->renderRss($items, $appUrl), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    // Monta os itens do feed a partir de uma lista de posts (PT ou EN).
    private function buildItems(array $posts, string $lang, string $appUrl): array
    {
        $prefix = $lang === 'en' ? '/en' : '';
        $items = [];

        foreach ($posts as $post) {
            $link = $appUrl . $prefix . '/post/' . $post['slug'];
            $items[] = [
                'title' => (string) $post['title'],
                'link' => $link,
                'summary' => (string) $post['summary'],
                'published_at' => (string) $post['published_at'],
                'ts' => (int) strtotime((string) $post['published_at']),
            ];
        }

        return $items;
    }

    private function renderRss(array $items, string $appUrl): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<rss version=\"2.0\">\n";
        $xml .= "  <channel>\n";
        $xml .= "    <title>osr.blog.br</title>\n";
        $xml .= '    <link>' . htmlspecialchars($appUrl . '/', ENT_QUOTES, 'UTF-8') . "</link>\n";
        $xml .= "    <description>Blog pessoal sobre RPG analógico (movimento OSR).</description>\n";

        foreach ($items as $item) {
            $xml .= "    <item>\n";
            $xml .= '      <title>' . htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') . "</title>\n";
            $xml .= '      <link>' . htmlspecialchars((string) $item['link'], ENT_QUOTES, 'UTF-8') . "</link>\n";
            $xml .= '      <guid>' . htmlspecialchars((string) $item['link'], ENT_QUOTES, 'UTF-8') . "</guid>\n";
            $xml .= '      <description>' . htmlspecialchars((string) $item['summary'], ENT_QUOTES, 'UTF-8') . "</description>\n";
            $xml .= '      <pubDate>' . $this->pubDate((string) $item['published_at']) . "</pubDate>\n";
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

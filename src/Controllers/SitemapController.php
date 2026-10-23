<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\CategoryRepository;
use App\Models\PostRepository;

/**
 * Gera o sitemap.xml com as URLs públicas (home, posts e categorias).
 */
final class SitemapController
{
    public function __construct(
        private PostRepository $posts,
        private CategoryRepository $categories
    ) {
    }

    public function index(Request $request): Response
    {
        $appUrl = rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost:8080'), '/');

        $posts = $this->posts->findAllPublished();
        $enPosts = $this->posts->findAllPublishedTranslations();
        $categories = $this->categories->findAll();

        $urls = [];

        // Home PT e EN (lastmod = post mais recente).
        $homeLastmod = $posts !== [] ? $this->lastmod($posts[0]) : date('Y-m-d');
        $urls[] = ['loc' => $appUrl . '/', 'lastmod' => $homeLastmod];
        $urls[] = ['loc' => $appUrl . '/en/', 'lastmod' => $homeLastmod];

        // Posts publicados (PT).
        foreach ($posts as $post) {
            $urls[] = [
                'loc' => $appUrl . '/post/' . $post['slug'],
                'lastmod' => $this->lastmod($post),
            ];
        }

        // Posts traduzidos (EN).
        foreach ($enPosts as $post) {
            $urls[] = [
                'loc' => $appUrl . '/en/post/' . $post['slug'],
                'lastmod' => $this->lastmod($post),
            ];
        }

        // Categorias (PT e EN).
        foreach ($categories as $category) {
            $lastmod = substr((string) $category['created_at'], 0, 10);
            $urls[] = ['loc' => $appUrl . '/categoria/' . $category['slug'], 'lastmod' => $lastmod];
            $urls[] = ['loc' => $appUrl . '/en/categoria/' . $category['slug'], 'lastmod' => $lastmod];
        }

        return new Response($this->renderXml($urls), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function lastmod(array $post): string
    {
        $value = $post['updated_at'] ?? $post['published_at'];

        return substr((string) $value, 0, 10);
    }

    private function renderXml(array $urls): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars((string) $url['loc'], ENT_QUOTES, 'UTF-8') . "</loc>\n";
            $xml .= '    <lastmod>' . htmlspecialchars((string) $url['lastmod'], ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>\n";

        return $xml;
    }
}

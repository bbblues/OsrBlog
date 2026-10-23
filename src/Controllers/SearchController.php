<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\PostRepository;
use App\Support\Lang;
use App\Support\Paginator;

/**
 * Controller da busca de posts.
 */
final class SearchController
{
    public function __construct(private PostRepository $posts)
    {
    }

    public function index(Request $request): Response
    {
        $lang = $request->lang();
        $prefix = $lang === 'en' ? '/en' : '';
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return View::render('public/search', [
                'title' => Lang::get('search.title', $lang) . ' — osr.blog.br',
                'description' => Lang::get('search.description', $lang),
                'query' => '',
                'posts' => [],
                'paginator' => new Paginator(0, 1),
                'baseUrl' => $prefix . '/busca',
                'lang' => $lang,
                'prefix' => $prefix,
                'i18n' => ['lang' => $lang, 'ptUrl' => '/busca', 'enUrl' => '/en/busca'],
            ]);
        }

        $baseUrl = $prefix . '/busca?q=' . urlencode($query);
        $total = $this->posts->countSearchPublished($query, $lang);

        $paginator = $this->resolvePage($request, $total, $baseUrl);
        if ($paginator instanceof Response) {
            return $paginator;
        }

        $posts = $this->posts->searchPublished($query, $paginator->limit(), $paginator->offset(), $lang);

        $title = Lang::get('search.title', $lang) . ': ' . $query . ' — osr.blog.br';
        if ($paginator->currentPage() > 1) {
            $title .= ' — ' . Lang::get('common.page', $lang) . ' ' . $paginator->currentPage();
        }

        return View::render('public/search', [
            'title' => $title,
            'description' => Lang::get('search.description_results', $lang) . ' "' . $query . '".',
            'query' => $query,
            'posts' => $posts,
            'paginator' => $paginator,
            'baseUrl' => $baseUrl,
            'lang' => $lang,
            'prefix' => $prefix,
            'i18n' => ['lang' => $lang, 'ptUrl' => '/busca?q=' . urlencode($query), 'enUrl' => '/en/busca?q=' . urlencode($query)],
        ]);
    }

    private function resolvePage(Request $request, int $total, string $baseUrl): Response|Paginator
    {
        $pageParam = $request->query('page');
        $page = $pageParam !== null ? (int) $pageParam : 1;
        if ($page < 1) {
            $page = 1;
        }

        if ($pageParam !== null && $page === 1) {
            return Response::redirect($baseUrl, 301);
        }

        $paginator = new Paginator($total, $page);
        if ($page > $paginator->totalPages()) {
            return $this->notFound();
        }

        return $paginator;
    }

    private function notFound(string $lang = 'pt'): Response
    {
        $prefix = $lang === 'en' ? '/en' : '';

        return View::render('public/404', [
            'title' => Lang::get('error.404', $lang),
            'lang' => $lang,
            'prefix' => $prefix,
            'i18n' => ['lang' => $lang, 'ptUrl' => '/', 'enUrl' => '/en/'],
        ])->setStatusCode(404);
    }
}

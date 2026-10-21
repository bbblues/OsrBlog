<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\PostRepository;
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
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return View::render('public/search', [
                'title' => 'Busca',
                'query' => '',
                'posts' => [],
                'paginator' => new Paginator(0, 1),
                'baseUrl' => '/busca',
            ]);
        }

        $total = $this->posts->countSearchPublished($query);

        $paginator = $this->resolvePage($request, $total, '/busca?q=' . urlencode($query));
        if ($paginator instanceof Response) {
            return $paginator;
        }

        $posts = $this->posts->searchPublished($query, $paginator->limit(), $paginator->offset());

        return View::render('public/search', [
            'title' => 'Busca: ' . $query,
            'query' => $query,
            'posts' => $posts,
            'paginator' => $paginator,
            'baseUrl' => '/busca?q=' . urlencode($query),
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

    private function notFound(): Response
    {
        return View::render('public/404', ['title' => 'Página não encontrada'])->setStatusCode(404);
    }
}

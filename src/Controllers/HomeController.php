<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\PostRepository;
use App\Support\Paginator;

/**
 * Controller da página inicial (lista de posts publicados).
 */
final class HomeController
{
    public function __construct(private PostRepository $posts)
    {
    }

    public function index(Request $request): Response
    {
        $total = $this->posts->countPublished();

        $paginator = $this->resolvePage($request, $total, '/');
        if ($paginator instanceof Response) {
            return $paginator;
        }

        $posts = $this->posts->findPublished($paginator->limit(), $paginator->offset());

        return View::render('public/home', [
            'title' => 'osr.blog.br',
            'posts' => $posts,
            'paginator' => $paginator,
            'baseUrl' => '/',
        ]);
    }

    // Resolve a página atual: trata ?page=1 (301) e página além do fim (404).
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

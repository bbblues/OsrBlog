<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\CategoryRepository;
use App\Models\PostRepository;
use App\Support\Paginator;

/**
 * Controller da listagem de posts por categoria.
 */
final class CategoryController
{
    public function __construct(
        private CategoryRepository $categories,
        private PostRepository $posts
    ) {
    }

    public function show(Request $request, string $slug): Response
    {
        $category = $this->categories->findBySlug($slug);

        if ($category === null) {
            return $this->notFound();
        }

        $total = $this->posts->countPublishedByCategory($slug);

        $paginator = $this->resolvePage($request, $total, '/categoria/' . $slug);
        if ($paginator instanceof Response) {
            return $paginator;
        }

        $posts = $this->posts->findPublishedByCategory($slug, $paginator->limit(), $paginator->offset());

        $title = (string) $category['name'] . ' — osr.blog.br';
        if ($paginator->currentPage() > 1) {
            $title .= ' — Página ' . $paginator->currentPage();
        }

        return View::render('public/category', [
            'title' => $title,
            'description' => 'Posts publicados na categoria ' . $category['name'] . '.',
            'category' => $category,
            'posts' => $posts,
            'paginator' => $paginator,
            'baseUrl' => '/categoria/' . $slug,
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

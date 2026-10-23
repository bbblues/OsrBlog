<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\CategoryRepository;
use App\Models\PostRepository;
use App\Support\Lang;
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
        $lang = $request->lang();
        $prefix = $lang === 'en' ? '/en' : '';

        $category = $this->categories->findBySlug($slug, $lang);

        if ($category === null) {
            return $this->notFound($lang);
        }

        $baseUrl = $prefix . '/categoria/' . $slug;
        $total = $this->posts->countPublishedByCategory($slug, $lang);

        $paginator = $this->resolvePage($request, $total, $baseUrl);
        if ($paginator instanceof Response) {
            return $paginator;
        }

        $posts = $this->posts->findPublishedByCategory($slug, $paginator->limit(), $paginator->offset(), $lang);

        $title = (string) $category['name'] . ' — osr.blog.br';
        if ($paginator->currentPage() > 1) {
            $title .= ' — ' . Lang::get('common.page', $lang) . ' ' . $paginator->currentPage();
        }

        return View::render('public/category', [
            'title' => $title,
            'description' => Lang::get('category.description', $lang) . ' ' . $category['name'] . '.',
            'category' => $category,
            'posts' => $posts,
            'paginator' => $paginator,
            'baseUrl' => $baseUrl,
            'lang' => $lang,
            'prefix' => $prefix,
            'i18n' => ['lang' => $lang, 'ptUrl' => '/categoria/' . $slug, 'enUrl' => '/en/categoria/' . $slug],
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

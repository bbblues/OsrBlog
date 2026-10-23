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
 * Controller da página inicial (lista de posts publicados).
 */
final class HomeController
{
    public function __construct(private PostRepository $posts)
    {
    }

    public function index(Request $request): Response
    {
        $lang = $request->lang();
        $prefix = $lang === 'en' ? '/en' : '';
        $baseUrl = $prefix . '/';

        // Na raiz sem prefixo, detecta o idioma preferido pelo navegador.
        if ($lang === 'pt' && $request->uri() === '/' && $request->query('page') === null) {
            if ($this->preferredLanguage($request) === 'en') {
                return Response::redirect('/en/', 302);
            }
        }

        $total = $this->posts->countPublished($lang);

        $paginator = $this->resolvePage($request, $total, $baseUrl);
        if ($paginator instanceof Response) {
            return $paginator;
        }

        $posts = $this->posts->findPublished($paginator->limit(), $paginator->offset(), $lang);

        $title = 'osr.blog.br';
        if ($paginator->currentPage() > 1) {
            $title .= ' — ' . Lang::get('common.page', $lang) . ' ' . $paginator->currentPage();
        }

        return View::render('public/home', [
            'title' => $title,
            'description' => Lang::get('home.description', $lang),
            'posts' => $posts,
            'paginator' => $paginator,
            'baseUrl' => $baseUrl,
            'lang' => $lang,
            'prefix' => $prefix,
            'i18n' => ['lang' => $lang, 'ptUrl' => '/', 'enUrl' => '/en/'],
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

    // Detecta o idioma preferido pelo cabeçalho Accept-Language ('pt' ou 'en').
    private function preferredLanguage(Request $request): string
    {
        $header = $request->header('Accept-Language');
        if ($header === null || trim($header) === '') {
            return 'pt';
        }

        $enPos = stripos($header, 'en');
        $ptPos = stripos($header, 'pt');

        if ($enPos !== false && ($ptPos === false || $enPos < $ptPos)) {
            return 'en';
        }

        return 'pt';
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

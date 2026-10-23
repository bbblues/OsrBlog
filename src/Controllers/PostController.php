<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\CommentRepository;
use App\Models\PostRepository;
use App\Support\Lang;
use App\Support\Markdown;

/**
 * Controller da página de um post individual.
 */
final class PostController
{
    public function __construct(
        private PostRepository $posts,
        private Markdown $markdown,
        private Session $session,
        private CommentRepository $comments,
        private Csrf $csrf
    ) {
    }

    public function show(Request $request, string $slug): Response
    {
        $lang = $request->lang();
        $prefix = $lang === 'en' ? '/en' : '';

        $post = $this->posts->findBySlug($slug, $lang);

        if ($post === null) {
            // Slug antigo (histórico, apenas PT) -> redirecionamento 301 para o slug novo.
            if ($lang === 'pt') {
                $newSlug = $this->posts->findNewSlugByOldSlug($slug);
                if ($newSlug !== null) {
                    return Response::redirect('/post/' . $newSlug, 301);
                }
            }

            return $this->notFound($lang);
        }

        // Publicado = published_at <= agora. Rascunho/agendado só no preview (admin logado).
        $isPublished = $post['published_at'] !== null
            && strtotime((string) $post['published_at']) <= time();

        if (!$isPublished && !$this->session->has('user_id')) {
            return $this->notFound($lang);
        }

        $post['body_html'] = $this->markdown->toHtml((string) $post['body_md']);
        $comments = $this->comments->findPublishedByPost((int) $post['id']);

        // URLs equivalentes entre PT e EN (para hreflang e seletor de idioma).
        if ($lang === 'en') {
            $enUrl = '/en/post/' . $slug;
            $ptPost = $this->posts->findById((int) $post['id']);
            $ptUrl = $ptPost !== null ? '/post/' . (string) $ptPost['slug'] : null;
        } else {
            $ptUrl = '/post/' . $slug;
            $en = $this->posts->findTranslationByPostId((int) $post['id'], 'en');
            $enUrl = $en !== null ? '/en/post/' . (string) $en['slug'] : null;
        }

        return View::render('public/post', [
            'title' => (string) $post['title'] . ' — osr.blog.br',
            'description' => (string) $post['summary'],
            'ogImage' => (string) ($post['cover_image'] ?? ''),
            'post' => $post,
            'comments' => $comments,
            'csrfField' => $this->csrf->field(),
            'lang' => $lang,
            'prefix' => $prefix,
            'i18n' => ['lang' => $lang, 'ptUrl' => $ptUrl, 'enUrl' => $enUrl],
        ]);
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

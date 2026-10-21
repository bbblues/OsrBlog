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
        $post = $this->posts->findBySlug($slug);

        if ($post === null) {
            // Slug antigo (histórico) -> redirecionamento 301 para o slug novo.
            $newSlug = $this->posts->findNewSlugByOldSlug($slug);
            if ($newSlug !== null) {
                return Response::redirect('/post/' . $newSlug, 301);
            }

            return $this->notFound();
        }

        // Publicado = published_at <= agora. Rascunho/agendado só no preview (admin logado).
        $isPublished = $post['published_at'] !== null
            && strtotime((string) $post['published_at']) <= time();

        if (!$isPublished && !$this->session->has('user_id')) {
            return $this->notFound();
        }

        $post['body_html'] = $this->markdown->toHtml((string) $post['body_md']);
        $comments = $this->comments->findPublishedByPost((int) $post['id']);

        return View::render('public/post', [
            'title' => (string) $post['title'] . ' — osr.blog.br',
            'description' => (string) $post['summary'],
            'ogImage' => (string) ($post['cover_image'] ?? ''),
            'post' => $post,
            'comments' => $comments,
            'csrfField' => $this->csrf->field(),
        ]);
    }

    private function notFound(): Response
    {
        return View::render('public/404', ['title' => 'Página não encontrada'])->setStatusCode(404);
    }
}

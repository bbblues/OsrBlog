<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\CommentRepository;
use App\Models\PostRepository;

/**
 * Controller do envio de comentários públicos.
 */
final class CommentController
{
    public function __construct(
        private CommentRepository $comments,
        private PostRepository $posts,
        private Session $session
    ) {
    }

    public function store(Request $request, string $slug): Response
    {
        $lang = $request->lang();
        $prefix = $lang === 'en' ? '/en' : '';

        $post = $this->posts->findBySlug($slug, $lang);

        if ($post === null) {
            return Response::redirect($prefix . '/');
        }

        $isPublished = $post['published_at'] !== null
            && strtotime((string) $post['published_at']) <= time();

        if (!$isPublished) {
            return Response::redirect($prefix . '/');
        }

        // Honeypot: campo oculto que apenas bots preenchem.
        if (trim((string) $request->post('website', '')) !== '') {
            return Response::redirect($prefix . '/post/' . $slug);
        }

        $name = trim((string) $request->post('author_name', ''));
        $body = trim((string) $request->post('body', ''));

        if ($name === '') {
            return $this->backWithError($prefix, $slug, 'Informe o seu nome.');
        }
        if (mb_strlen($name) > 80) {
            return $this->backWithError($prefix, $slug, 'O nome deve ter no máximo 80 caracteres.');
        }
        if ($body === '') {
            return $this->backWithError($prefix, $slug, 'O comentário não pode estar vazio.');
        }

        // Rejeita comentários com links (validação no servidor).
        if (preg_match('/https?:\/\/|www\./i', $body) || preg_match('/https?:\/\/|www\./i', $name)) {
            return $this->backWithError($prefix, $slug, 'Comentários não podem conter links.');
        }

        // Rate limit: 5 comentários por hora por IP.
        $ipHash = $this->ipHash();
        if ($this->comments->countRecentByIpHash($ipHash) >= 5) {
            return $this->backWithError($prefix, $slug, 'Muitos comentários enviados. Tente novamente mais tarde.');
        }

        $id = $this->comments->insert([
            'post_id' => (int) $post['id'],
            'author_name' => $name,
            'body' => $body,
            'status' => 'published',
            'ip_hash' => $ipHash,
        ]);

        $this->session->flash('success', 'Comentário publicado!');

        return Response::redirect($prefix . '/post/' . $slug . '#comentario-' . $id);
    }

    private function backWithError(string $prefix, string $slug, string $message): Response
    {
        $this->session->flash('error', $message);

        return Response::redirect($prefix . '/post/' . $slug . '#comentar');
    }

    // Hash SHA-256 do IP (truncado para o tamanho da coluna CHAR(16)).
    private function ipHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $salt = $_ENV['ANALYTICS_IP_SALT'] ?? '';

        return substr(hash('sha256', $ip . '|' . $salt), 0, 16);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\CommentRepository;
use App\Models\PostRepository;
use App\Support\Paginator;

/**
 * Moderação de comentários do painel administrativo.
 */
final class CommentController
{
    public function __construct(
        private CommentRepository $comments,
        private PostRepository $posts,
        private Session $session,
        private Csrf $csrf
    ) {
    }

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $status = is_string($status) ? $status : '';
        $postId = $request->query('post');
        $postId = $postId !== null && $postId !== '' ? (int) $postId : null;
        $flagged = $request->query('sinalizados') === '1';

        // Monta a URL base da paginação preservando os filtros.
        $baseUrl = '/admin/comentarios';
        $queryParts = [];
        if ($status !== '') {
            $queryParts[] = 'status=' . urlencode($status);
        }
        if ($postId !== null) {
            $queryParts[] = 'post=' . $postId;
        }
        if ($flagged) {
            $queryParts[] = 'sinalizados=1';
        }
        if ($queryParts !== []) {
            $baseUrl .= '?' . implode('&', $queryParts);
        }

        $total = $this->comments->countWithFilters($status !== '' ? $status : null, $postId, $flagged);

        $paginator = $this->resolvePage($request, $total, $baseUrl);
        if ($paginator instanceof Response) {
            return $paginator;
        }

        $comments = $this->comments->findWithFilters(
            $status !== '' ? $status : null,
            $postId,
            $flagged,
            $paginator->limit(),
            $paginator->offset()
        );

        // Badges de sinalização (mesmo IP/autor de um comentário marcado como spam).
        $spamIps = $this->comments->spamIpHashes();
        $spamAuthors = $this->comments->spamAuthors();

        foreach ($comments as &$comment) {
            $comment['same_ip'] = $comment['status'] !== 'spam'
                && $comment['ip_hash'] !== null
                && in_array($comment['ip_hash'], $spamIps, true);
            $comment['same_author'] = $comment['status'] !== 'spam'
                && in_array($comment['author_name'], $spamAuthors, true);
        }
        unset($comment);

        return View::render('admin/comments/index', [
            'title' => 'Comentários',
            'comments' => $comments,
            'paginator' => $paginator,
            'baseUrl' => $baseUrl,
            'statusFilter' => $status,
            'postFilter' => $postId,
            'flaggedFilter' => $flagged,
            'posts' => $this->posts->findAllTitles(),
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }

    public function status(Request $request, string $id): Response
    {
        $status = (string) $request->post('status', '');

        if (!in_array($status, ['published', 'spam', 'trash'], true)) {
            $this->session->flash('error', 'Status inválido.');

            return Response::redirect('/admin/comentarios');
        }

        $this->comments->updateStatus((int) $id, $status);
        $this->session->flash('success', 'Status do comentário atualizado.');

        return Response::redirect('/admin/comentarios');
    }

    public function destroy(Request $request, string $id): Response
    {
        $comment = $this->comments->findById((int) $id);

        if ($comment === null) {
            $this->session->flash('error', 'Comentário não encontrado.');

            return Response::redirect('/admin/comentarios');
        }

        if ($comment['status'] !== 'trash') {
            $this->session->flash('error', 'Só é possível excluir permanentemente comentários na lixeira.');

            return Response::redirect('/admin/comentarios');
        }

        $this->comments->delete((int) $id);
        $this->session->flash('success', 'Comentário excluído permanentemente.');

        return Response::redirect('/admin/comentarios');
    }

    public function batch(Request $request): Response
    {
        $action = (string) $request->post('action', '');
        $ids = $this->normalizeIds($request->post('ids'));

        if ($ids === []) {
            $this->session->flash('error', 'Nenhum comentário selecionado.');

            return Response::redirect('/admin/comentarios');
        }

        $map = ['spam' => 'spam', 'trash' => 'trash', 'restore' => 'published'];

        if (!isset($map[$action])) {
            $this->session->flash('error', 'Ação em lote inválida.');

            return Response::redirect('/admin/comentarios');
        }

        $this->comments->batchUpdateStatus($ids, $map[$action]);
        $this->session->flash('success', 'Ação em lote aplicada.');

        return Response::redirect('/admin/comentarios');
    }

    private function normalizeIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
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

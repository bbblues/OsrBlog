<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\CategoryRepository;
use App\Models\PostRepository;
use App\Support\ImageUpload;
use App\Support\Paginator;
use App\Support\Slugger;
use App\Support\Translator;

/**
 * CRUD de posts do painel administrativo.
 */
final class PostController
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(
        private PostRepository $posts,
        private CategoryRepository $categories,
        private Slugger $slugger,
        private Translator $translator,
        private ImageUpload $imageUpload,
        private Session $session,
        private Csrf $csrf
    ) {
    }

    // Lista todos os posts (qualquer status) com paginação.
    public function index(Request $request): Response
    {
        $total = $this->posts->countAll();

        $paginator = $this->resolvePage($request, $total, '/admin/posts');
        if ($paginator instanceof Response) {
            return $paginator;
        }

        $posts = $this->posts->findAll($paginator->limit(), $paginator->offset());

        return View::render('admin/posts/index', [
            'title' => 'Posts',
            'posts' => $posts,
            'paginator' => $paginator,
            'baseUrl' => '/admin/posts',
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }

    // Exibe o formulário de criação (sem id) ou edição (com id).
    public function form(Request $request, ?string $id = null): Response
    {
        $categories = $this->categories->findAll();

        $post = null;
        $selectedIds = [];
        $formAction = '/admin/posts';
        $statusValue = 'rascunho';
        $publishedAtValue = '';

        if ($id !== null) {
            $post = $this->posts->findById((int) $id);
            if ($post === null) {
                return $this->notFound();
            }

            $selectedIds = array_column($post['categories'], 'id');
            $formAction = '/admin/posts/' . $post['id'];

            if ($post['published_at'] !== null) {
                $timestamp = strtotime((string) $post['published_at']);
                if ($timestamp <= time()) {
                    $statusValue = 'publicar';
                } else {
                    $statusValue = 'agendar';
                    $publishedAtValue = date('Y-m-d\TH:i', $timestamp);
                }
            }
        }

        return View::render('admin/posts/form', [
            'title' => $post === null ? 'Novo post' : 'Editar post',
            'post' => $post,
            'categories' => $categories,
            'selectedIds' => $selectedIds,
            'formAction' => $formAction,
            'statusValue' => $statusValue,
            'publishedAtValue' => $publishedAtValue,
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }

    // Cria um novo post.
    public function store(Request $request): Response
    {
        $result = $this->validateAndSave($request, null);
        if (is_string($result)) {
            $this->session->flash('error', $result);

            return Response::redirect('/admin/posts/criar');
        }

        $this->translateToEnglish($result, $request);
        $this->session->flash('success', 'Post criado com sucesso.');

        return Response::redirect('/admin/posts');
    }

    // Atualiza um post existente.
    public function update(Request $request, string $id): Response
    {
        $post = $this->posts->findById((int) $id);
        if ($post === null) {
            return $this->notFound();
        }

        $result = $this->validateAndSave($request, $post);
        if (is_string($result)) {
            $this->session->flash('error', $result);

            return Response::redirect('/admin/posts/' . $post['id'] . '/editar');
        }

        $this->translateToEnglish($result, $request);
        $this->session->flash('success', 'Post atualizado com sucesso.');

        return Response::redirect('/admin/posts');
    }

    // Exclui um post.
    public function destroy(Request $request, string $id): Response
    {
        $post = $this->posts->findById((int) $id);
        if ($post === null) {
            return $this->notFound();
        }

        $this->posts->delete((int) $id);
        $this->session->flash('success', 'Post excluído.');

        return Response::redirect('/admin/posts');
    }

    // Valida e persiste o post (cria ou atualiza). Retorna o id do post ou uma mensagem de erro.
    private function validateAndSave(Request $request, ?array $existing): int|string
    {
        $title = trim((string) $request->post('title', ''));
        $summary = trim((string) $request->post('summary', ''));
        $body = (string) $request->post('body_md', '');
        $slug = trim((string) $request->post('slug', ''));

        if ($title === '') {
            return 'O título é obrigatório.';
        }
        if (mb_strlen($title) > 200) {
            return 'O título deve ter no máximo 200 caracteres.';
        }
        if ($summary === '') {
            return 'O resumo é obrigatório.';
        }
        if (mb_strlen($summary) > 500) {
            return 'O resumo deve ter no máximo 500 caracteres.';
        }

        // Slug: gera se vazio, valida o formato e a unicidade.
        if ($slug === '') {
            $slug = $this->slugger->slugify($title);
        }
        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            return 'Slug inválido. Use apenas letras minúsculas, números e hífens.';
        }
        $ignoreId = $existing !== null ? (int) $existing['id'] : null;
        if ($this->slugger->exists($slug, $ignoreId)) {
            return 'Este slug já está em uso por outro post.';
        }

        // Status -> published_at.
        $publishedAt = $this->resolvePublishedAt($request);
        if ($publishedAt === false) {
            return 'Data de agendamento inválida.';
        }

        // Upload de imagem (opcional).
        $coverImage = $existing !== null ? $existing['cover_image'] : null;
        $file = $_FILES['cover_image'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $coverImage = '/uploads/' . $this->imageUpload->store($file);
            } catch (\RuntimeException $e) {
                return $e->getMessage();
            }
        }

        $categoryIds = $this->normalizeCategoryIds($request->post('categories'));

        $data = [
            'title' => $title,
            'slug' => $slug,
            'summary' => $summary,
            'body_md' => $body,
            'cover_image' => $coverImage,
            'published_at' => $publishedAt,
        ];

        if ($existing === null) {
            return $this->posts->create($data, $categoryIds);
        }

        $this->posts->update((int) $existing['id'], $data, $categoryIds);

        // Se o slug mudou e o post já estava publicado, registra o slug antigo.
        $oldSlug = (string) $existing['slug'];
        if ($oldSlug !== $slug && $this->isPublished($existing)) {
            $this->posts->saveSlugHistory((int) $existing['id'], $oldSlug);
        }

        return (int) $existing['id'];
    }

    // Mapeia o campo "status" do formulário para o valor de published_at.
    private function resolvePublishedAt(Request $request): string|null|false
    {
        $status = (string) $request->post('status', 'rascunho');

        if ($status === 'publicar') {
            return date('Y-m-d H:i:s');
        }

        if ($status === 'agendar') {
            $value = trim((string) $request->post('published_at', ''));
            $value = str_replace('T', ' ', $value);
            if ($value === '') {
                return false;
            }
            if (strlen($value) === 16) {
                $value .= ':00';
            }
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return false;
            }

            return date('Y-m-d H:i:s', $timestamp);
        }

        return null; // rascunho
    }

    private function normalizeCategoryIds(mixed $value): array
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

    private function isPublished(array $post): bool
    {
        return $post['published_at'] !== null
            && strtotime((string) $post['published_at']) <= time();
    }

    // Traduz o post para o inglês e persiste em post_translations.
    // Falhas na API ou no banco nunca interrompem o fluxo: apenas geram um aviso.
    private function translateToEnglish(int $postId, Request $request): void
    {
        $title = trim((string) $request->post('title', ''));
        $summary = trim((string) $request->post('summary', ''));
        $body = (string) $request->post('body_md', '');

        try {
            $translation = $this->translator->translatePost($title, $summary, $body);
        } catch (\Throwable) {
            $translation = null;
        }

        if (!is_array($translation)) {
            $this->session->flash('warning', 'Post salvo em PT, mas a tradução automática falhou.');

            return;
        }

        $lang = 'en';
        $translatedTitle = $this->truncate((string) $translation['title'], 200);
        $slug = $this->slugger->slugify($translatedTitle);
        if ($slug === '') {
            $slug = 'post-' . $postId;
        }
        $slug = $this->truncate($slug, 200);
        $slug = $this->uniqueTranslationSlug($slug, $postId);

        $data = [
            'language' => $lang,
            'title' => $translatedTitle,
            'slug' => $slug,
            'summary' => $this->truncate((string) $translation['summary'], 500),
            'body_md' => (string) $translation['body_md'],
        ];

        try {
            if ($this->posts->findTranslationByPostId($postId, $lang) === null) {
                $this->posts->saveTranslation($postId, $data);
            } else {
                $this->posts->updateTranslation($postId, $lang, $data);
            }
        } catch (\Throwable) {
            $this->session->flash('warning', 'Post salvo em PT, mas a tradução automática falhou.');
        }
    }

    // Garante unicidade do slug de tradução dentro de post_translations.
    private function uniqueTranslationSlug(string $base, int $postId): string
    {
        $candidate = $base;
        $counter = 2;

        while (true) {
            $existing = $this->posts->findTranslationBySlug($candidate);
            if ($existing === null || (int) $existing['post_id'] === $postId) {
                return $candidate;
            }

            $candidate = $base . '-' . $counter;
            $counter++;
        }
    }

    // Trunca uma string para o tamanho máximo permitido pela coluna.
    private function truncate(string $value, int $length): string
    {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length) : $value;
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

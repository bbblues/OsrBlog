<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\CategoryRepository;
use App\Support\Slugger;

/**
 * CRUD de categorias do painel administrativo.
 */
final class CategoryController
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(
        private CategoryRepository $categories,
        private Slugger $slugger,
        private Session $session,
        private Csrf $csrf
    ) {
    }

    // Lista as categorias (com formulários inline de criar/editar).
    public function index(Request $request): Response
    {
        return View::render('admin/categories/index', [
            'title' => 'Categorias',
            'categories' => $this->categories->findAll(),
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }

    public function store(Request $request): Response
    {
        $error = $this->validate($request, null);
        if ($error !== null) {
            $this->session->flash('error', $error);

            return Response::redirect('/admin/categorias');
        }

        $this->categories->create($this->dataFromRequest($request));
        $this->session->flash('success', 'Categoria criada.');

        return Response::redirect('/admin/categorias');
    }

    public function update(Request $request, string $id): Response
    {
        $category = $this->categories->findById((int) $id);
        if ($category === null) {
            $this->session->flash('error', 'Categoria não encontrada.');

            return Response::redirect('/admin/categorias');
        }

        $error = $this->validate($request, (int) $id);
        if ($error !== null) {
            $this->session->flash('error', $error);

            return Response::redirect('/admin/categorias');
        }

        $this->categories->update((int) $id, $this->dataFromRequest($request));
        $this->session->flash('success', 'Categoria atualizada.');

        return Response::redirect('/admin/categorias');
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->categories->delete((int) $id);
        $this->session->flash('success', 'Categoria excluída.');

        return Response::redirect('/admin/categorias');
    }

    // Valida nome e slug. Retorna mensagem de erro ou null.
    private function validate(Request $request, ?int $ignoreId): ?string
    {
        $name = trim((string) $request->post('name', ''));
        $nameEn = trim((string) $request->post('name_en', ''));
        $slug = trim((string) $request->post('slug', ''));

        if ($name === '') {
            return 'O nome é obrigatório.';
        }
        if (mb_strlen($name) > 80) {
            return 'O nome deve ter no máximo 80 caracteres.';
        }
        if ($nameEn !== '' && mb_strlen($nameEn) > 80) {
            return 'O nome em inglês deve ter no máximo 80 caracteres.';
        }

        if ($slug === '') {
            $slug = $this->slugger->slugify($name);
        }
        if (!preg_match(self::SLUG_PATTERN, $slug)) {
            return 'Slug inválido. Use apenas letras minúsculas, números e hífens.';
        }
        if ($this->categories->slugExists($slug, $ignoreId)) {
            return 'Este slug já está em uso por outra categoria.';
        }

        return null;
    }

    // Monta o array de dados a partir do POST.
    private function dataFromRequest(Request $request): array
    {
        $name = trim((string) $request->post('name', ''));
        $nameEn = trim((string) $request->post('name_en', ''));
        $slug = trim((string) $request->post('slug', ''));

        if ($slug === '') {
            $slug = $this->slugger->slugify($name);
        }

        return [
            'name' => $name,
            'name_en' => $nameEn === '' ? null : $nameEn,
            'slug' => $slug,
        ];
    }
}

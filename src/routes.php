<?php

declare(strict_types=1);

use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Controllers\PostController;
use App\Controllers\SearchController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Models\CategoryRepository;
use App\Models\PostRepository;
use App\Support\Markdown;

/**
 * Tabela de rotas da aplicação.
 *
 * O CsrfMiddleware é global (registrado no front controller) e vale para todo
 * POST; o AuthMiddleware é aplicado apenas às rotas administrativas protegidas.
 */
return function (Router $router, AuthMiddleware $auth, Session $session): void {
    // Infraestrutura de dados e suporte.
    $pdo = Database::getInstance()->getPdo();
    $postRepository = new PostRepository($pdo);
    $categoryRepository = new CategoryRepository($pdo);
    $markdown = new Markdown();

    // Controllers da área pública.
    $homeController = new HomeController($postRepository);
    $postController = new PostController($postRepository, $markdown, $session);
    $categoryController = new CategoryController($categoryRepository, $postRepository);
    $searchController = new SearchController($postRepository);

    // ------------------------------------------------------------------
    // Rotas públicas
    // ------------------------------------------------------------------
    $router->get('/', [$homeController, 'index']);
    $router->get('/post/{slug}', [$postController, 'show']);
    $router->get('/categoria/{slug}', [$categoryController, 'show']);
    $router->get('/busca', [$searchController, 'index']);

    // Comentários (Fase 5) e SEO (Fase 6): ainda como closures provisórias.
    $router->post('/post/{slug}/comentar', fn(Request $request, string $slug) => Response::make('Rota POST /post/' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '/comentar acessada'));
    $router->get('/sitemap.xml', fn(Request $request) => Response::make('Rota /sitemap.xml acessada'));
    $router->get('/feed', fn(Request $request) => Response::make('Rota /feed acessada'));
    $router->get('/rss.xml', fn(Request $request) => Response::make('Rota /rss.xml acessada'));
    $router->get('/robots.txt', fn(Request $request) => Response::make('Rota /robots.txt acessada'));

    // ------------------------------------------------------------------
    // Rotas administrativas
    // ------------------------------------------------------------------
    // Autenticação (sem AuthMiddleware).
    $router->get('/admin/login', fn(Request $request) => Response::make('Rota /admin/login acessada'));
    $router->post('/admin/login', fn(Request $request) => Response::make('Rota POST /admin/login acessada'));
    $router->post('/admin/logout', fn(Request $request) => Response::make('Rota POST /admin/logout acessada'));

    // Áreas protegidas (com AuthMiddleware).
    $router->get('/admin', fn(Request $request) => Response::make('Rota /admin (dashboard) acessada'), [$auth, 'handle']);

    // Posts (CRUD).
    $router->get('/admin/posts', fn(Request $request) => Response::make('Rota /admin/posts acessada'), [$auth, 'handle']);
    $router->get('/admin/posts/criar', fn(Request $request) => Response::make('Rota /admin/posts/criar acessada'), [$auth, 'handle']);
    $router->post('/admin/posts', fn(Request $request) => Response::make('Rota POST /admin/posts acessada'), [$auth, 'handle']);
    $router->get('/admin/posts/{id}/editar', fn(Request $request, string $id) => Response::make('Rota /admin/posts/' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '/editar acessada'), [$auth, 'handle']);
    $router->post('/admin/posts/{id}', fn(Request $request, string $id) => Response::make('Rota POST /admin/posts/' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . ' acessada'), [$auth, 'handle']);
    $router->post('/admin/posts/{id}/excluir', fn(Request $request, string $id) => Response::make('Rota POST /admin/posts/' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '/excluir acessada'), [$auth, 'handle']);

    // Categorias (CRUD).
    $router->get('/admin/categorias', fn(Request $request) => Response::make('Rota /admin/categorias acessada'), [$auth, 'handle']);
    $router->get('/admin/categorias/criar', fn(Request $request) => Response::make('Rota /admin/categorias/criar acessada'), [$auth, 'handle']);
    $router->post('/admin/categorias', fn(Request $request) => Response::make('Rota POST /admin/categorias acessada'), [$auth, 'handle']);
    $router->get('/admin/categorias/{id}/editar', fn(Request $request, string $id) => Response::make('Rota /admin/categorias/' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '/editar acessada'), [$auth, 'handle']);
    $router->post('/admin/categorias/{id}', fn(Request $request, string $id) => Response::make('Rota POST /admin/categorias/' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . ' acessada'), [$auth, 'handle']);
    $router->post('/admin/categorias/{id}/excluir', fn(Request $request, string $id) => Response::make('Rota POST /admin/categorias/' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '/excluir acessada'), [$auth, 'handle']);

    // Comentários (moderação).
    $router->get('/admin/comentarios', fn(Request $request) => Response::make('Rota /admin/comentarios acessada'), [$auth, 'handle']);
    $router->post('/admin/comentarios/{id}/aprovar', fn(Request $request, string $id) => Response::make('Rota POST /admin/comentarios/' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '/aprovar acessada'), [$auth, 'handle']);
    $router->post('/admin/comentarios/{id}/excluir', fn(Request $request, string $id) => Response::make('Rota POST /admin/comentarios/' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '/excluir acessada'), [$auth, 'handle']);

    // Estatísticas e backup.
    $router->get('/admin/estatisticas', fn(Request $request) => Response::make('Rota /admin/estatisticas acessada'), [$auth, 'handle']);
    $router->get('/admin/backup', fn(Request $request) => Response::make('Rota /admin/backup acessada'), [$auth, 'handle']);
    $router->post('/admin/backup', fn(Request $request) => Response::make('Rota POST /admin/backup acessada'), [$auth, 'handle']);
};

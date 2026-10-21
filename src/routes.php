<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PostController as AdminPostController;
use App\Controllers\CategoryController;
use App\Controllers\HomeController;
use App\Controllers\PostController;
use App\Controllers\SearchController;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Models\CategoryRepository;
use App\Models\PostRepository;
use App\Models\UserRepository;
use App\Support\ImageUpload;
use App\Support\Markdown;
use App\Support\Slugger;

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

    // Infraestrutura e controllers da área administrativa.
    $userRepository = new UserRepository($pdo);
    $slugger = new Slugger($pdo);
    $imageUpload = new ImageUpload(dirname(__DIR__) . '/storage/uploads');
    $csrf = new Csrf($session);

    $authController = new AuthController($userRepository, $session, $csrf);
    $dashboardController = new DashboardController($postRepository, $session, $csrf);
    $adminPostController = new AdminPostController(
        $postRepository,
        $categoryRepository,
        $slugger,
        $imageUpload,
        $session,
        $csrf
    );

    // ------------------------------------------------------------------
    // Rotas públicas
    // ------------------------------------------------------------------
    $router->get('/', [$homeController, 'index']);
    $router->get('/post/{slug}', [$postController, 'show']);
    $router->get('/categoria/{slug}', [$categoryController, 'show']);
    $router->get('/busca', [$searchController, 'index']);

    // Serve as imagens enviadas via upload (storage/uploads), com validação de path.
    $router->get('/uploads/{filename}', function (Request $request, string $filename): Response {
        $base = realpath(dirname(__DIR__) . '/storage/uploads');
        if ($base === false) {
            return Response::notFound();
        }

        $path = realpath($base . DIRECTORY_SEPARATOR . $filename);
        if ($path === false || !str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            return Response::notFound();
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!is_string($mime) || !str_starts_with($mime, 'image/')) {
            return Response::notFound();
        }

        $content = file_get_contents($path);

        return new Response($content === false ? '' : $content, 200, ['Content-Type' => $mime]);
    });

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
    $router->get('/admin/login', [$authController, 'form']);
    $router->post('/admin/login', [$authController, 'login']);
    $router->post('/admin/logout', [$authController, 'logout']);

    // Áreas protegidas (com AuthMiddleware).
    $router->get('/admin', [$dashboardController, 'index'], [$auth, 'handle']);

    // Posts (CRUD).
    $router->get('/admin/posts', [$adminPostController, 'index'], [$auth, 'handle']);
    $router->get('/admin/posts/criar', [$adminPostController, 'form'], [$auth, 'handle']);
    $router->post('/admin/posts', [$adminPostController, 'store'], [$auth, 'handle']);
    $router->get('/admin/posts/{id}/editar', [$adminPostController, 'form'], [$auth, 'handle']);
    $router->post('/admin/posts/{id}', [$adminPostController, 'update'], [$auth, 'handle']);
    $router->post('/admin/posts/{id}/excluir', [$adminPostController, 'destroy'], [$auth, 'handle']);

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

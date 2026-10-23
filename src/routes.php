<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BackupController as AdminBackupController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\CommentController as AdminCommentController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PostController as AdminPostController;
use App\Controllers\Admin\ProfileController;
use App\Controllers\Admin\StatsController;
use App\Controllers\CategoryController;
use App\Controllers\CommentController;
use App\Controllers\FeedController;
use App\Controllers\HomeController;
use App\Controllers\PostController;
use App\Controllers\RobotsController;
use App\Controllers\SearchController;
use App\Controllers\SitemapController;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Models\CategoryRepository;
use App\Models\CommentRepository;
use App\Models\PostRepository;
use App\Models\UserRepository;
use App\Support\Analytics;
use App\Support\Backup;
use App\Support\ImageUpload;
use App\Support\Markdown;
use App\Support\Slugger;
use App\Support\Translator;

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
    $commentRepository = new CommentRepository($pdo);
    $markdown = new Markdown();
    $slugger = new Slugger($pdo);
    $translator = new Translator();
    $imageUpload = new ImageUpload(dirname(__DIR__) . '/storage/uploads');
    $csrf = new Csrf($session);

    // Analytics e backup.
    $analytics = new Analytics(dirname(__DIR__) . '/storage/logs', $_ENV['ANALYTICS_IP_SALT'] ?? '');
    $backup = new Backup(
        dirname(__DIR__) . '/storage/backups',
        dirname(__DIR__) . '/storage/uploads',
        dirname(__DIR__) . '/storage/logs',
        $pdo,
        [
            'host' => DB_HOST,
            'port' => DB_PORT,
            'name' => DB_NAME,
            'user' => DB_USER,
            'password' => DB_PASSWORD,
        ]
    );

    // Controllers da área pública.
    $homeController = new HomeController($postRepository);
    $postController = new PostController($postRepository, $markdown, $session, $commentRepository, $csrf);
    $categoryController = new CategoryController($categoryRepository, $postRepository);
    $searchController = new SearchController($postRepository);
    $commentController = new CommentController($commentRepository, $postRepository, $session);
    $sitemapController = new SitemapController($postRepository, $categoryRepository);
    $feedController = new FeedController($postRepository);
    $robotsController = new RobotsController();

    // Infraestrutura e controllers da área administrativa.
    $userRepository = new UserRepository($pdo);

    $authController = new AuthController($userRepository, $session, $csrf);
    $profileController = new ProfileController($userRepository, $session, $csrf);
    $dashboardController = new DashboardController($postRepository, $session, $csrf, $analytics, $backup);
    $adminPostController = new AdminPostController(
        $postRepository,
        $categoryRepository,
        $slugger,
        $translator,
        $imageUpload,
        $session,
        $csrf
    );
    $adminCategoryController = new AdminCategoryController($categoryRepository, $slugger, $session, $csrf);
    $adminCommentController = new AdminCommentController($commentRepository, $postRepository, $session, $csrf);
    $statsController = new StatsController($analytics);
    $adminBackupController = new AdminBackupController($backup, $session, $csrf);

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

    // Comentário público e SEO.
    $router->post('/post/{slug}/comentar', [$commentController, 'store']);
    $router->get('/sitemap.xml', [$sitemapController, 'index']);
    $router->get('/feed', [$feedController, 'index']);
    $router->get('/rss.xml', [$feedController, 'index']);
    $router->get('/robots.txt', [$robotsController, 'index']);

    // ------------------------------------------------------------------
    // Rotas administrativas
    // ------------------------------------------------------------------
    // Autenticação (sem AuthMiddleware).
    $router->get('/admin/login', [$authController, 'form']);
    $router->post('/admin/login', [$authController, 'login']);
    $router->post('/admin/logout', [$authController, 'logout']);

    // Áreas protegidas (com AuthMiddleware).
    $router->get('/admin', [$dashboardController, 'index'], [$auth, 'handle']);

    // Perfil do administrador (alteração de senha).
    $router->get('/admin/perfil', [$profileController, 'form'], [$auth, 'handle']);
    $router->post('/admin/perfil', [$profileController, 'update'], [$auth, 'handle']);

    // Posts (CRUD).
    $router->get('/admin/posts', [$adminPostController, 'index'], [$auth, 'handle']);
    $router->get('/admin/posts/criar', [$adminPostController, 'form'], [$auth, 'handle']);
    $router->post('/admin/posts', [$adminPostController, 'store'], [$auth, 'handle']);
    $router->get('/admin/posts/{id}/editar', [$adminPostController, 'form'], [$auth, 'handle']);
    $router->post('/admin/posts/{id}', [$adminPostController, 'update'], [$auth, 'handle']);
    $router->post('/admin/posts/{id}/excluir', [$adminPostController, 'destroy'], [$auth, 'handle']);

    // Categorias (CRUD).
    $router->get('/admin/categorias', [$adminCategoryController, 'index'], [$auth, 'handle']);
    $router->post('/admin/categorias', [$adminCategoryController, 'store'], [$auth, 'handle']);
    $router->post('/admin/categorias/{id}', [$adminCategoryController, 'update'], [$auth, 'handle']);
    $router->post('/admin/categorias/{id}/excluir', [$adminCategoryController, 'destroy'], [$auth, 'handle']);

    // Comentários (moderação).
    $router->get('/admin/comentarios', [$adminCommentController, 'index'], [$auth, 'handle']);
    $router->post('/admin/comentarios/batch', [$adminCommentController, 'batch'], [$auth, 'handle']);
    $router->post('/admin/comentarios/{id}/status', [$adminCommentController, 'status'], [$auth, 'handle']);
    $router->post('/admin/comentarios/{id}/excluir', [$adminCommentController, 'destroy'], [$auth, 'handle']);

    // Estatísticas e backup.
    $router->get('/admin/estatisticas', [$statsController, 'index'], [$auth, 'handle']);
    $router->get('/admin/backup', [$adminBackupController, 'index'], [$auth, 'handle']);
    $router->post('/admin/backup', [$adminBackupController, 'create'], [$auth, 'handle']);
    $router->get('/admin/backup/download/{filename}', [$adminBackupController, 'download'], [$auth, 'handle']);
    $router->post('/admin/backup/{filename}/excluir', [$adminBackupController, 'destroy'], [$auth, 'handle']);
};

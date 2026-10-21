<?php

declare(strict_types=1);

use App\Core\Response;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

/**
 * Tabela de rotas da aplicação (Fase 2: handlers provisórios em closures).
 *
 * O CsrfMiddleware é global (registrado no front controller) e vale para todo
 * POST; o AuthMiddleware é aplicado apenas às rotas administrativas protegidas.
 */
return function (Router $router, AuthMiddleware $auth): void {
    // ------------------------------------------------------------------
    // Rotas públicas
    // ------------------------------------------------------------------
    $router->get('/', fn() => Response::make('Rota home acessada'));
    $router->get('/post/{slug}', fn($slug) => Response::make('Rota /post/' . htmlspecialchars((string) $slug, ENT_QUOTES, 'UTF-8') . ' acessada'));
    $router->get('/categoria/{slug}', fn($slug) => Response::make('Rota /categoria/' . htmlspecialchars((string) $slug, ENT_QUOTES, 'UTF-8') . ' acessada'));
    $router->get('/busca', fn() => Response::make('Rota /busca acessada'));
    $router->post('/post/{slug}/comentar', fn($slug) => Response::make('Rota POST /post/' . htmlspecialchars((string) $slug, ENT_QUOTES, 'UTF-8') . '/comentar acessada'));
    $router->get('/sitemap.xml', fn() => Response::make('Rota /sitemap.xml acessada'));
    $router->get('/feed', fn() => Response::make('Rota /feed acessada'));
    $router->get('/rss.xml', fn() => Response::make('Rota /rss.xml acessada'));
    $router->get('/robots.txt', fn() => Response::make('Rota /robots.txt acessada'));

    // ------------------------------------------------------------------
    // Rotas administrativas
    // ------------------------------------------------------------------
    // Autenticação (sem AuthMiddleware).
    $router->get('/admin/login', fn() => Response::make('Rota /admin/login acessada'));
    $router->post('/admin/login', fn() => Response::make('Rota POST /admin/login acessada'));
    $router->post('/admin/logout', fn() => Response::make('Rota POST /admin/logout acessada'));

    // Áreas protegidas (com AuthMiddleware).
    $router->get('/admin', fn() => Response::make('Rota /admin (dashboard) acessada'), [$auth, 'handle']);

    // Posts (CRUD).
    $router->get('/admin/posts', fn() => Response::make('Rota /admin/posts acessada'), [$auth, 'handle']);
    $router->get('/admin/posts/criar', fn() => Response::make('Rota /admin/posts/criar acessada'), [$auth, 'handle']);
    $router->post('/admin/posts', fn() => Response::make('Rota POST /admin/posts acessada'), [$auth, 'handle']);
    $router->get('/admin/posts/{id}/editar', fn($id) => Response::make('Rota /admin/posts/' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '/editar acessada'), [$auth, 'handle']);
    $router->post('/admin/posts/{id}', fn($id) => Response::make('Rota POST /admin/posts/' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . ' acessada'), [$auth, 'handle']);
    $router->post('/admin/posts/{id}/excluir', fn($id) => Response::make('Rota POST /admin/posts/' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '/excluir acessada'), [$auth, 'handle']);

    // Categorias (CRUD).
    $router->get('/admin/categorias', fn() => Response::make('Rota /admin/categorias acessada'), [$auth, 'handle']);
    $router->get('/admin/categorias/criar', fn() => Response::make('Rota /admin/categorias/criar acessada'), [$auth, 'handle']);
    $router->post('/admin/categorias', fn() => Response::make('Rota POST /admin/categorias acessada'), [$auth, 'handle']);
    $router->get('/admin/categorias/{id}/editar', fn($id) => Response::make('Rota /admin/categorias/' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '/editar acessada'), [$auth, 'handle']);
    $router->post('/admin/categorias/{id}', fn($id) => Response::make('Rota POST /admin/categorias/' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . ' acessada'), [$auth, 'handle']);
    $router->post('/admin/categorias/{id}/excluir', fn($id) => Response::make('Rota POST /admin/categorias/' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '/excluir acessada'), [$auth, 'handle']);

    // Comentários (moderação).
    $router->get('/admin/comentarios', fn() => Response::make('Rota /admin/comentarios acessada'), [$auth, 'handle']);
    $router->post('/admin/comentarios/{id}/aprovar', fn($id) => Response::make('Rota POST /admin/comentarios/' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '/aprovar acessada'), [$auth, 'handle']);
    $router->post('/admin/comentarios/{id}/excluir', fn($id) => Response::make('Rota POST /admin/comentarios/' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '/excluir acessada'), [$auth, 'handle']);

    // Estatísticas e backup.
    $router->get('/admin/estatisticas', fn() => Response::make('Rota /admin/estatisticas acessada'), [$auth, 'handle']);
    $router->get('/admin/backup', fn() => Response::make('Rota /admin/backup acessada'), [$auth, 'handle']);
    $router->post('/admin/backup', fn() => Response::make('Rota POST /admin/backup acessada'), [$auth, 'handle']);
};

<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\PostRepository;

/**
 * Dashboard do painel administrativo.
 */
final class DashboardController
{
    public function __construct(
        private PostRepository $posts,
        private Session $session,
        private Csrf $csrf
    ) {
    }

    public function index(Request $request): Response
    {
        return View::render('admin/dashboard', [
            'title' => 'Dashboard',
            'totalPosts' => $this->posts->countAll(),
            'publishedPosts' => $this->posts->countPublished(),
            'draftPosts' => $this->posts->countDrafts(),
            'scheduledPosts' => $this->posts->countScheduled(),
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\PostRepository;
use App\Support\Analytics;
use App\Support\Backup;

/**
 * Dashboard do painel administrativo.
 */
final class DashboardController
{
    public function __construct(
        private PostRepository $posts,
        private Session $session,
        private Csrf $csrf,
        private Analytics $analytics,
        private Backup $backup
    ) {
    }

    public function index(Request $request): Response
    {
        // Último backup (para aviso de > 7 dias).
        $backups = $this->backup->list();
        $lastBackupAt = $backups !== [] ? (int) $backups[0]['mtime'] : null;
        $backupWarning = $lastBackupAt === null || (time() - $lastBackupAt) > 7 * 86400;

        // Resumo de acessos dos últimos 7 dias.
        $week = $this->analytics->summaryLastDays(7);

        return View::render('admin/dashboard', [
            'title' => 'Dashboard',
            'totalPosts' => $this->posts->countAll(),
            'publishedPosts' => $this->posts->countPublished(),
            'draftPosts' => $this->posts->countDrafts(),
            'scheduledPosts' => $this->posts->countScheduled(),
            'backupWarning' => $backupWarning,
            'weekPageviews' => $week['pageviews'],
            'weekUnique' => $week['unique'],
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }
}

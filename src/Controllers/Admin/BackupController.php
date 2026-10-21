<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Support\Backup;

/**
 * Gerenciamento de backups do painel administrativo.
 */
final class BackupController
{
    public function __construct(
        private Backup $backup,
        private Session $session,
        private Csrf $csrf
    ) {
    }

    public function index(Request $request): Response
    {
        return View::render('admin/backup/index', [
            'title' => 'Backup',
            'backups' => $this->backup->list(),
            'csrfField' => $this->csrf->field(),
            'flashMessages' => $this->session->allFlash(),
        ]);
    }

    public function create(Request $request): Response
    {
        try {
            $filename = $this->backup->create();
            $this->session->flash('success', 'Backup criado: ' . $filename);
        } catch (\Throwable $e) {
            $this->session->flash('error', 'Falha ao criar o backup: ' . $e->getMessage());
        }

        return Response::redirect('/admin/backup');
    }

    public function download(Request $request, string $filename): Response
    {
        $path = $this->backup->resolvePath($filename);
        if ($path === null) {
            return Response::notFound();
        }

        $content = file_get_contents($path);

        return new Response($content === false ? '' : $content, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . basename($filename) . '"',
        ]);
    }

    public function destroy(Request $request, string $filename): Response
    {
        $this->backup->delete($filename);
        $this->session->flash('success', 'Backup removido.');

        return Response::redirect('/admin/backup');
    }
}

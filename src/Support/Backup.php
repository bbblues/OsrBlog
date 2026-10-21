<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use ZipArchive;

/**
 * Gera backups (.zip) do banco, uploads e logs, com retenção dos mais recentes.
 */
final class Backup
{
    public function __construct(
        private string $backupDir,
        private string $uploadsDir,
        private string $logsDir,
        private PDO $pdo,
        private array $db = []
    ) {
    }

    // Gera o backup e retorna o nome do arquivo criado.
    public function create(): string
    {
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }

        $sql = $this->dumpDatabase();

        $filename = 'backup-' . date('Y-m-d-His') . '.zip';
        $filepath = $this->backupDir . DIRECTORY_SEPARATOR . $filename;

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Não foi possível criar o arquivo de backup.');
        }

        $zip->addFromString('database.sql', $sql);
        $zip->addFromString('MANIFEST.txt', $this->manifest($sql));

        $this->addDirectory($zip, $this->uploadsDir, 'uploads');
        $this->addDirectory($zip, $this->logsDir, 'logs');

        $zip->close();

        $this->cleanup();

        return $filename;
    }

    // Lista os backups existentes (mais recentes primeiro).
    public function list(): array
    {
        $files = glob($this->backupDir . '/*.zip');
        if ($files === false) {
            return [];
        }

        $items = [];
        foreach ($files as $file) {
            $items[] = [
                'filename' => basename($file),
                'size' => (int) filesize($file),
                'created_at' => date('Y-m-d H:i:s', (int) filemtime($file)),
                'mtime' => (int) filemtime($file),
            ];
        }

        usort($items, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

        return $items;
    }

    // Remove um backup pelo nome do arquivo.
    public function delete(string $filename): void
    {
        $path = $this->resolvePath($filename);
        if ($path !== null) {
            @unlink($path);
        }
    }

    // Resolve o caminho seguro de um backup (evita path traversal).
    public function resolvePath(string $filename): ?string
    {
        $base = realpath($this->backupDir);
        if ($base === false) {
            return null;
        }

        $path = realpath($base . DIRECTORY_SEPARATOR . $filename);
        if ($path === false || !str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $path;
    }

    private function dumpDatabase(): string
    {
        $dump = $this->dumpViaMysqldump();
        if ($dump !== null) {
            return $dump;
        }

        return $this->dumpViaPdo();
    }

    private function dumpViaMysqldump(): ?string
    {
        if (!function_exists('exec')) {
            return null;
        }

        $host = $this->db['host'] ?? 'mysql';
        $port = $this->db['port'] ?? '3306';
        $name = $this->db['name'] ?? 'osr_blog';
        $user = $this->db['user'] ?? 'osr';
        $password = $this->db['password'] ?? '';

        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --skip-comments %s 2>/dev/null',
            escapeshellarg((string) $host),
            escapeshellarg((string) $port),
            escapeshellarg((string) $user),
            escapeshellarg((string) $password),
            escapeshellarg((string) $name)
        );

        $output = [];
        $returnCode = 0;
        @exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || $output === []) {
            return null;
        }

        return implode("\n", $output);
    }

    private function dumpViaPdo(): string
    {
        $sql = "-- Backup gerado via PDO (mysqldump indisponível)\n";
        $sql .= "SET NAMES utf8mb4;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $table = (string) $table;
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                continue;
            }

            $create = $this->pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM);
            $sql .= $create[1] . ";\n\n";

            $rows = $this->pdo->query('SELECT * FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(fn($value) => $this->quoteValue($value), array_values($row));
                $sql .= 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', $values) . ");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $sql;
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    private function manifest(string $sql): string
    {
        $lines = [
            'osr.blog.br — Backup',
            'Data: ' . date('Y-m-d H:i:s'),
            'PHP: ' . PHP_VERSION,
            'Posts: ' . (int) $this->pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
            'Categorias: ' . (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'Comentários: ' . (int) $this->pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn(),
            'Usuários: ' . (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'SHA-256 (database.sql): ' . hash('sha256', $sql),
        ];

        return implode("\n", $lines) . "\n";
    }

    private function addDirectory(ZipArchive $zip, string $dir, string $zipPath): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $zip->addEmptyDir($zipPath);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $filePath = $file->getPathname();
            $relative = $zipPath . '/' . substr($filePath, strlen(rtrim($dir, '/\\')) + 1);
            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($filePath, $relative);
            }
        }
    }

    private function cleanup(): void
    {
        $keep = (int) ($_ENV['BACKUPS_TO_KEEP'] ?? 5);
        $files = glob($this->backupDir . '/*.zip');
        if ($files === false) {
            return;
        }

        usort($files, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));

        $files = array_slice($files, $keep);
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

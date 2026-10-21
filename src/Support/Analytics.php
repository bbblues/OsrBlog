<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Registra acessos (pageviews) em arquivos TSV mensais em storage/logs.
 * Não usa banco de dados — apenas arquivos TSV.
 */
final class Analytics
{
    public function __construct(
        private string $logsDir,
        private string $ipSalt = ''
    ) {
    }

    // Registra a requisição atual, se aplicável (GET público, 200/301/404, não-bot).
    public function track(int $statusCode): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($method !== 'GET') {
            return;
        }
        if (!in_array($statusCode, [200, 301, 404], true)) {
            return;
        }
        if (str_starts_with($path, '/admin')) {
            return;
        }
        if ($this->isBot($userAgent)) {
            return;
        }

        $line = implode("\t", [
            date('Y-m-d H:i:s'),
            $this->ipHash((string) ($_SERVER['REMOTE_ADDR'] ?? '')),
            $this->sanitize($path),
            $this->referrerHost((string) ($_SERVER['HTTP_REFERER'] ?? '')),
            $this->detectDevice($userAgent),
            (string) $statusCode,
        ]) . "\n";

        $this->write($line);
    }

    // Lê todas as entradas TSV existentes.
    public function entries(): array
    {
        $entries = [];
        $files = glob($this->logsDir . '/analytics-*.tsv');
        if ($files === false) {
            return [];
        }
        sort($files);

        foreach ($files as $file) {
            $handle = @fopen($file, 'r');
            if ($handle === false) {
                continue;
            }
            while (($line = fgets($handle)) !== false) {
                $fields = explode("\t", rtrim($line, "\r\n"));
                if (count($fields) < 6) {
                    continue;
                }
                $entries[] = [
                    'timestamp' => $fields[0],
                    'ip_hash' => $fields[1],
                    'path' => $fields[2],
                    'referrer' => $fields[3],
                    'device' => $fields[4],
                    'status' => (int) $fields[5],
                ];
            }
            fclose($handle);
        }

        return $entries;
    }

    // Resumo de acessos dos últimos N dias (para o dashboard).
    public function summaryLastDays(int $days = 7): array
    {
        $cutoff = date('Y-m-d', strtotime('-' . $days . ' days'));
        $pageviews = 0;
        $ips = [];

        foreach ($this->entries() as $entry) {
            if (substr((string) $entry['timestamp'], 0, 10) < $cutoff) {
                continue;
            }
            $pageviews++;
            if ($entry['ip_hash'] !== '') {
                $ips[(string) $entry['ip_hash']] = true;
            }
        }

        return [
            'pageviews' => $pageviews,
            'unique' => count($ips),
        ];
    }

    // ------------------------------------------------------------------
    // Helpers internos
    // ------------------------------------------------------------------

    private function write(string $line): void
    {
        if (!is_dir($this->logsDir)) {
            @mkdir($this->logsDir, 0755, true);
        }

        $filepath = $this->logsDir . DIRECTORY_SEPARATOR . 'analytics-' . date('Y-m') . '.tsv';

        // Primeira escrita do mês: limpa arquivos com mais de 12 meses.
        $isFirstWriteOfMonth = !is_file($filepath);

        @file_put_contents($filepath, $line, FILE_APPEND | LOCK_EX);

        if ($isFirstWriteOfMonth) {
            $this->cleanupOldFiles();
        }
    }

    private function cleanupOldFiles(): void
    {
        $cutoff = date('Y-m', strtotime('-12 months'));
        $files = glob($this->logsDir . '/analytics-*.tsv');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $month = substr(basename($file, '.tsv'), -7);
            if ($month !== '' && $month < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function ipHash(string $ip): string
    {
        return substr(hash('sha256', $ip . '|' . $this->ipSalt), 0, 16);
    }

    private function referrerHost(string $referrer): string
    {
        if ($referrer === '') {
            return '';
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        return is_string($host) ? $host : '';
    }

    private function detectDevice(string $userAgent): string
    {
        if (preg_match('/Tablet|iPad/i', $userAgent)) {
            return 'tablet';
        }
        if (preg_match('/Mobile|Android|iPhone|iPod/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function isBot(string $userAgent): bool
    {
        return preg_match('/bot|crawler|spider|slurp|curl|wget|bingpreview|facebookexternalhit|headless/i', $userAgent) === 1;
    }

    private function sanitize(string $value): string
    {
        return str_replace(["\t", "\n", "\r"], ' ', $value);
    }
}

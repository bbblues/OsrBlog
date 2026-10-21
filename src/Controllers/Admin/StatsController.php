<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Support\Analytics;

/**
 * Estatísticas de acessos (lê os arquivos TSV do Analytics).
 */
final class StatsController
{
    public function __construct(private Analytics $analytics)
    {
    }

    public function index(Request $request): Response
    {
        $entries = $this->analytics->entries();

        $pageviews = count($entries);
        $unique = count(array_unique(array_column($entries, 'ip_hash')));

        // Gráfico de linha por dia.
        $byDay = [];
        foreach ($entries as $entry) {
            $day = substr((string) $entry['timestamp'], 0, 10);
            $byDay[$day] = ($byDay[$day] ?? 0) + 1;
        }
        ksort($byDay);

        return View::render('admin/stats/index', [
            'title' => 'Estatísticas',
            'pageviews' => $pageviews,
            'uniqueVisitors' => $unique,
            'chartLabels' => $this->json(array_keys($byDay)),
            'chartData' => $this->json(array_values($byDay)),
            'topPages' => array_slice($this->countBy($entries, 'path'), 0, 10, true),
            'topReferrers' => array_slice($this->countBy($entries, 'referrer'), 0, 10, true),
            'devices' => $this->countBy($entries, 'device'),
            'top404' => array_slice($this->countBy(array_filter($entries, static fn($e) => $e['status'] === 404), 'path'), 0, 10, true),
            'top301' => array_slice($this->countBy(array_filter($entries, static fn($e) => $e['status'] === 301), 'path'), 0, 10, true),
        ]);
    }

    private function countBy(array $entries, string $key): array
    {
        $counts = [];
        foreach ($entries as $entry) {
            $value = (string) ($entry[$key] ?? '');
            if ($value === '') {
                $value = '(direto)';
            }
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }
        arsort($counts);

        return $counts;
    }

    private function json(array $value): string
    {
        return (string) json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Calcula offset, limite e total de páginas para a paginação (?page=N).
 */
final class Paginator
{
    public function __construct(
        private int $totalItems,
        private int $currentPage,
        private int $perPage = 10
    ) {
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }
    }

    public function totalPages(): int
    {
        return max(1, (int) ceil($this->totalItems / $this->perPage));
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function limit(): int
    {
        return $this->perPage;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages();
    }

    public function previousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function nextPage(): int
    {
        return min($this->totalPages(), $this->currentPage + 1);
    }

    // Números de página para exibição na navegação.
    public function pages(): array
    {
        return range(1, $this->totalPages());
    }
}

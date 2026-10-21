<?php if ($paginator->totalPages() > 1): ?>
    <?php $sep = str_contains($baseUrl, '?') ? '&' : '?'; ?>
    <nav aria-label="Paginação">
        <ul class="pagination">
            <?php if ($paginator->hasPrevious()): ?>
                <?php $prev = $paginator->previousPage(); ?>
                <li class="page-item">
                    <a class="page-link" href="<?= htmlspecialchars($prev === 1 ? $baseUrl : ($baseUrl . $sep . 'page=' . $prev), ENT_QUOTES, 'UTF-8') ?>">&laquo; Anterior</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">&laquo; Anterior</span></li>
            <?php endif; ?>

            <?php foreach ($paginator->pages() as $page): ?>
                <?php if ($page === $paginator->currentPage()): ?>
                    <li class="page-item active" aria-current="page"><span class="page-link"><?= $page ?></span></li>
                <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($page === 1 ? $baseUrl : ($baseUrl . $sep . 'page=' . $page), ENT_QUOTES, 'UTF-8') ?>"><?= $page ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($paginator->hasNext()): ?>
                <?php $next = $paginator->nextPage(); ?>
                <li class="page-item">
                    <a class="page-link" href="<?= htmlspecialchars($baseUrl . $sep . 'page=' . $next, ENT_QUOTES, 'UTF-8') ?>">Próxima &raquo;</a>
                </li>
            <?php else: ?>
                <li class="page-item disabled"><span class="page-link">Próxima &raquo;</span></li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

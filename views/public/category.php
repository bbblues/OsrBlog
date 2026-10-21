<h1 class="mb-4">Categoria: <?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></h1>

<?php if (empty($posts)): ?>
    <p class="text-muted">Nenhum post publicado nesta categoria.</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <article class="card mb-4">
            <div class="card-body">
                <h2 class="h4 mb-2">
                    <a href="/post/<?= htmlspecialchars((string) $post['slug'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                        <?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </h2>
                <p class="text-muted small mb-2">
                    <?= date('d/m/Y', strtotime((string) $post['published_at'])) ?>
                </p>
                <p class="mb-0"><?= htmlspecialchars((string) $post['summary'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/pagination.php'; ?>

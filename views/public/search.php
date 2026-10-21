<h1 class="mb-4">Busca</h1>

<form method="get" action="/busca" class="mb-4">
    <div class="input-group">
        <input type="search" name="q" value="<?= htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Buscar posts...">
        <button type="submit" class="btn btn-outline-secondary">Buscar</button>
    </div>
</form>

<?php if ($query === ''): ?>
    <p class="text-muted">Digite um termo para buscar.</p>
<?php elseif (empty($posts)): ?>
    <p class="text-muted">Nenhum resultado para "<?= htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8') ?>".</p>
<?php else: ?>
    <p class="text-muted"><?= count($posts) ?> resultado(s) para "<?= htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8') ?>".</p>
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

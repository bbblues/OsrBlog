<h1 class="mb-4"><?= htmlspecialchars(\App\Support\Lang::get('search.title', $lang), ENT_QUOTES, 'UTF-8') ?></h1>

<form method="get" action="<?= htmlspecialchars($prefix . '/busca', ENT_QUOTES, 'UTF-8') ?>" class="mb-4">
    <div class="input-group">
        <input type="search" name="q" value="<?= htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="<?= htmlspecialchars(\App\Support\Lang::get('search.placeholder', $lang), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-outline-secondary"><?= htmlspecialchars(\App\Support\Lang::get('search.button', $lang), ENT_QUOTES, 'UTF-8') ?></button>
    </div>
</form>

<?php if ($query === ''): ?>
    <p class="text-muted"><?= htmlspecialchars(\App\Support\Lang::get('search.prompt', $lang), ENT_QUOTES, 'UTF-8') ?></p>
<?php elseif (empty($posts)): ?>
    <p class="text-muted"><?= htmlspecialchars(\App\Support\Lang::get('search.no_results', $lang), ENT_QUOTES, 'UTF-8') ?> &quot;<?= htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8') ?>&quot;.</p>
<?php else: ?>
    <p class="text-muted"><?= count($posts) ?> <?= htmlspecialchars(\App\Support\Lang::get('search.results', $lang), ENT_QUOTES, 'UTF-8') ?> &quot;<?= htmlspecialchars((string) $query, ENT_QUOTES, 'UTF-8') ?>&quot;.</p>
    <?php foreach ($posts as $post): ?>
        <article class="card mb-4">
            <div class="card-body">
                <h2 class="h4 mb-2">
                    <a href="<?= htmlspecialchars($prefix . '/post/' . (string) $post['slug'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
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

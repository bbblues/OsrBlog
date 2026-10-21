<article>
    <header class="mb-4">
        <h1 class="mb-2"><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted small mb-0">
            Publicado em <?= date('d/m/Y', strtotime((string) $post['published_at'])) ?>
            <?php if (!empty($post['categories'])): ?>
                &middot;
                <?php foreach ($post['categories'] as $i => $category): ?>
                    <?php if ($i > 0): ?>, <?php endif; ?>
                    <a href="/categoria/<?= htmlspecialchars((string) $category['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </p>
    </header>

    <?php if (!empty($post['cover_image'])): ?>
        <img src="<?= htmlspecialchars((string) $post['cover_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded mb-4">
    <?php endif; ?>

    <div class="markdown-body">
        <?= $post['body_html'] ?>
    </div>
</article>

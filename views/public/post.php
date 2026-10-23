<article>
    <header class="mb-4">
        <h1 class="mb-2"><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted small mb-0">
            <?= htmlspecialchars(\App\Support\Lang::get('post.published_in', $lang), ENT_QUOTES, 'UTF-8') ?> <?= date('d/m/Y', strtotime((string) $post['published_at'])) ?>
            <?php if (!empty($post['categories'])): ?>
                &middot;
                <?php foreach ($post['categories'] as $i => $category): ?>
                    <?php if ($i > 0): ?>, <?php endif; ?>
                    <a href="<?= htmlspecialchars($prefix . '/categoria/' . (string) $category['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></a>
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

<section class="mt-5" id="comentarios">
    <h2 class="h4 mb-3"><?= htmlspecialchars(\App\Support\Lang::get('post.comments', $lang), ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if (empty($comments)): ?>
        <p class="text-muted"><?= htmlspecialchars(\App\Support\Lang::get('post.no_comments', $lang), ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <?php foreach ($comments as $comment): ?>
            <div class="card mb-3" id="comentario-<?= (int) $comment['id'] ?>">
                <div class="card-body">
                    <p class="mb-1"><strong><?= htmlspecialchars((string) $comment['author_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                    <p class="text-muted small mb-2"><?= date('d/m/Y H:i', strtotime((string) $comment['created_at'])) ?></p>
                    <p class="mb-0"><?= nl2br(htmlspecialchars((string) $comment['body'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="card mt-4" id="comentar">
        <div class="card-body">
            <h3 class="h5 mb-3"><?= htmlspecialchars(\App\Support\Lang::get('comment.leave_comment', $lang), ENT_QUOTES, 'UTF-8') ?></h3>
            <form method="post" action="<?= htmlspecialchars($prefix . '/post/' . (string) $post['slug'], ENT_QUOTES, 'UTF-8') ?>/comentar">
                <?= $csrfField ?>
                <div class="mb-3" style="display:none" aria-hidden="true">
                    <label for="website"><?= htmlspecialchars(\App\Support\Lang::get('comment.website', $lang), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="mb-3">
                    <label for="author_name" class="form-label"><?= htmlspecialchars(\App\Support\Lang::get('comment.name', $lang), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="text" name="author_name" id="author_name" class="form-control" maxlength="80" required>
                </div>
                <div class="mb-3">
                    <label for="body" class="form-label"><?= htmlspecialchars(\App\Support\Lang::get('comment.comment', $lang), ENT_QUOTES, 'UTF-8') ?></label>
                    <textarea name="body" id="body" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(\App\Support\Lang::get('comment.submit', $lang), ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </div>
    </div>
</section>


<?php require __DIR__ . '/partials/header.php'; ?>

<main class="container py-4">
    <?php if (!empty($flashMessages)): ?>
        <?php foreach ($flashMessages as $type => $message): ?>
            <div class="alert alert-<?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?>" role="alert">
                <?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?= $content ?? '' ?>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

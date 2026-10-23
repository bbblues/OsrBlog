<?php $prefix = (string) ($prefix ?? ''); $lang = (string) ($lang ?? 'pt'); ?>
<div class="text-center py-5">
    <h1 class="display-4">404</h1>
    <p class="lead"><?= htmlspecialchars(\App\Support\Lang::get('error.404', $lang), ENT_QUOTES, 'UTF-8') ?>.</p>
    <a href="<?= htmlspecialchars($prefix . '/', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary"><?= htmlspecialchars(\App\Support\Lang::get('error.home', $lang), ENT_QUOTES, 'UTF-8') ?></a>
</div>

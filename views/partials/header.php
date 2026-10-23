<!DOCTYPE html>
<html lang="<?= (($i18n['lang'] ?? 'pt') === 'en') ? 'en' : 'pt-BR' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/meta.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php if (!empty($i18n)): ?>
    <?php $i18nLang = (string) ($i18n['lang'] ?? 'pt'); ?>
    <?php $ptHref = isset($i18n['ptUrl']) ? (string) $i18n['ptUrl'] : '/'; ?>
    <?php $enHref = isset($i18n['enUrl']) ? (string) $i18n['enUrl'] : '/en/'; ?>
    <div class="container d-flex justify-content-end py-2">
        <nav aria-label="<?= htmlspecialchars(\App\Support\Lang::get('lang.switcher', $i18nLang), ENT_QUOTES, 'UTF-8') ?>" class="small">
            <?php if ($i18nLang === 'pt'): ?>
                <span class="fw-bold text-body-secondary">🇧🇷 PT</span>
            <?php else: ?>
                <a href="<?= htmlspecialchars($ptHref, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">🇧🇷 PT</a>
            <?php endif; ?>
            <span class="text-body-secondary mx-1">|</span>
            <?php if ($i18nLang === 'en'): ?>
                <span class="fw-bold text-body-secondary">🇺🇸 EN</span>
            <?php else: ?>
                <a href="<?= htmlspecialchars($enHref, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">🇺🇸 EN</a>
            <?php endif; ?>
        </nav>
    </div>
<?php endif; ?>
<?php if (!empty($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/admin">Painel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#admin-nav" aria-controls="admin-nav" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="admin-nav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/admin">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/posts">Posts</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/categorias">Categorias</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/comentarios">Comentários</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/estatisticas">Estatísticas</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/backup">Backup</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link active" href="/admin/perfil">Meu Perfil</a></li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

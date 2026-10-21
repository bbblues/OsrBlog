<h1 class="mb-4">Dashboard</h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6"><?= (int) $totalPosts ?></div>
                <div class="text-muted">Posts</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6"><?= (int) $publishedPosts ?></div>
                <div class="text-muted">Publicados</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6"><?= (int) $draftPosts ?></div>
                <div class="text-muted">Rascunhos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6"><?= (int) $scheduledPosts ?></div>
                <div class="text-muted">Agendados</div>
            </div>
        </div>
    </div>
</div>

<?php if ($backupWarning): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span>O último backup tem mais de 7 dias (ou ainda não foi feito).</span>
        <a href="/admin/backup" class="btn btn-sm btn-outline-warning">Ir para backup</a>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <div class="text-muted small">Acessos (últimos 7 dias)</div>
            <div class="fs-5"><?= (int) $weekPageviews ?> pageviews · <?= (int) $weekUnique ?> únicos</div>
        </div>
        <a href="/admin/estatisticas" class="btn btn-sm btn-outline-primary">Ver estatísticas</a>
    </div>
</div>

<div class="d-flex gap-2">
    <a href="/admin/posts" class="btn btn-primary">Gerenciar posts</a>
    <a href="/admin/posts/criar" class="btn btn-outline-primary">Novo post</a>
    <a href="/admin/estatisticas" class="btn btn-outline-secondary">Estatísticas</a>
    <a href="/admin/backup" class="btn btn-outline-secondary">Backup</a>
    <form method="post" action="/admin/logout" class="ms-auto">
        <?= $csrfField ?>
        <button type="submit" class="btn btn-outline-secondary">Sair</button>
    </form>
</div>

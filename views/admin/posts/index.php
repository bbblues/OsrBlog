<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2 mb-0">Posts</h1>
    <a href="/admin/posts/criar" class="btn btn-primary">Novo post</a>
</div>

<?php if (empty($posts)): ?>
    <p class="text-muted">Nenhum post cadastrado.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Status</th>
                    <th>Publicado em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <?php
                    $statusLabel = 'Rascunho';
                    if ($post['published_at'] !== null) {
                        $statusLabel = strtotime((string) $post['published_at']) <= time() ? 'Publicado' : 'Agendado';
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $statusLabel ?></td>
                        <td><?= $post['published_at'] !== null ? date('d/m/Y H:i', strtotime((string) $post['published_at'])) : '—' ?></td>
                        <td class="text-end">
                            <a href="/admin/posts/<?= (int) $post['id'] ?>/editar" class="btn btn-sm btn-outline-secondary">Editar</a>
                            <form method="post" action="/admin/posts/<?= (int) $post['id'] ?>/excluir" class="d-inline" onsubmit="return confirm('Excluir este post?');">
                                <?= $csrfField ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../partials/pagination.php'; ?>

<h1 class="h2 mb-4">Comentários</h1>

<form method="get" action="/admin/comentarios" class="row g-2 mb-4">
    <div class="col-md-4">
        <select name="status" class="form-select">
            <option value="">Todos os status</option>
            <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Publicados</option>
            <option value="spam" <?= $statusFilter === 'spam' ? 'selected' : '' ?>>Spam</option>
            <option value="trash" <?= $statusFilter === 'trash' ? 'selected' : '' ?>>Lixeira</option>
        </select>
    </div>
    <div class="col-md-4">
        <select name="post" class="form-select">
            <option value="">Todos os posts</option>
            <?php foreach ($posts as $post): ?>
                <option value="<?= (int) $post['id'] ?>" <?= $postFilter !== null && (int) $postFilter === (int) $post['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-center">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="sinalizados" value="1" id="flag" <?= $flaggedFilter ? 'checked' : '' ?>>
            <label class="form-check-label" for="flag">Sinalizados</label>
        </div>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
    </div>
</form>

<form method="post" action="/admin/comentarios/batch" id="batch-form" class="mb-3">
    <?= $csrfField ?>
    <div class="btn-group">
        <button type="submit" name="action" value="spam" class="btn btn-outline-warning">Marcar spam</button>
        <button type="submit" name="action" value="trash" class="btn btn-outline-secondary">Mover p/ lixeira</button>
        <button type="submit" name="action" value="restore" class="btn btn-outline-success">Restaurar</button>
    </div>
</form>

<?php if (empty($comments)): ?>
    <p class="text-muted">Nenhum comentário encontrado.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Autor</th>
                    <th>Comentário</th>
                    <th>Post</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td>
                            <input type="checkbox" form="batch-form" name="ids[]" value="<?= (int) $comment['id'] ?>">
                        </td>
                        <td>
                            <div><?= htmlspecialchars((string) $comment['author_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-muted small"><?= date('d/m/Y H:i', strtotime((string) $comment['created_at'])) ?></div>
                            <?php if ($comment['same_ip']): ?><span class="badge text-bg-warning me-1">mesmo IP</span><?php endif; ?>
                            <?php if ($comment['same_author']): ?><span class="badge text-bg-warning">mesmo autor</span><?php endif; ?>
                        </td>
                        <td class="text-break"><?= nl2br(htmlspecialchars((string) $comment['body'], ENT_QUOTES, 'UTF-8')) ?></td>
                        <td><a href="/post/<?= htmlspecialchars((string) $comment['post_slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $comment['post_title'], ENT_QUOTES, 'UTF-8') ?></a></td>
                        <td>
                            <?php $badge = match ($comment['status']) { 'published' => 'success', 'spam' => 'warning', default => 'secondary' }; ?>
                            <span class="badge text-bg-<?= $badge ?>"><?= htmlspecialchars((string) $comment['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td class="text-end">
                            <form method="post" action="/admin/comentarios/<?= (int) $comment['id'] ?>/status" class="d-flex gap-1 justify-content-end mb-1">
                                <?= $csrfField ?>
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="published" <?= $comment['status'] === 'published' ? 'selected' : '' ?>>Publicado</option>
                                    <option value="spam" <?= $comment['status'] === 'spam' ? 'selected' : '' ?>>Spam</option>
                                    <option value="trash" <?= $comment['status'] === 'trash' ? 'selected' : '' ?>>Lixeira</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Aplicar</button>
                            </form>
                            <?php if ($comment['status'] === 'trash'): ?>
                                <form method="post" action="/admin/comentarios/<?= (int) $comment['id'] ?>/excluir" onsubmit="return confirm('Excluir permanentemente?');">
                                    <?= $csrfField ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../partials/pagination.php'; ?>

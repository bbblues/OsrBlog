<h1 class="h2 mb-4">Backup</h1>

<form method="post" action="/admin/backup" class="mb-4">
    <?= $csrfField ?>
    <button type="submit" class="btn btn-primary">Gerar backup agora</button>
</form>

<?php if (empty($backups)): ?>
    <p class="text-muted">Nenhum backup criado ainda.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Tamanho</th>
                    <th>Criado em</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $backup['filename'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((int) $backup['size'] / 1024, 1) ?> KB</td>
                        <td><?= htmlspecialchars((string) $backup['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a href="/admin/backup/download/<?= htmlspecialchars((string) $backup['filename'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Baixar</a>
                            <form method="post" action="/admin/backup/<?= htmlspecialchars((string) $backup['filename'], ENT_QUOTES, 'UTF-8') ?>/excluir" class="d-inline" onsubmit="return confirm('Excluir este backup?');">
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

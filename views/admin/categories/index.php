<h1 class="h2 mb-4">Categorias</h1>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3">Nova categoria</h2>
        <form method="post" action="/admin/categorias" class="row g-2">
            <?= $csrfField ?>
            <div class="col-md-5">
                <input type="text" name="name" class="form-control" placeholder="Nome" maxlength="80" required>
            </div>
            <div class="col-md-5">
                <input type="text" name="slug" class="form-control" placeholder="Slug (opcional)" pattern="[a-z0-9]+(-[a-z0-9]+)*">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Criar</button>
            </div>
        </form>
    </div>
</div>

<?php if (empty($categories)): ?>
    <p class="text-muted">Nenhuma categoria cadastrada.</p>
<?php else: ?>
    <div class="list-group">
        <?php foreach ($categories as $category): ?>
            <div class="list-group-item">
                <form method="post" action="/admin/categorias/<?= (int) $category['id'] ?>" class="row g-2 align-items-center">
                    <?= $csrfField ?>
                    <div class="col-md-5">
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?>" maxlength="80" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars((string) $category['slug'], ENT_QUOTES, 'UTF-8') ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*" required>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-outline-secondary flex-fill">Salvar</button>
                        <button type="submit" formaction="/admin/categorias/<?= (int) $category['id'] ?>/excluir" formmethod="post" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir esta categoria?');">Excluir</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

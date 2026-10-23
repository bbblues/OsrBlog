<h1 class="mb-4"><?= $post === null ? 'Novo post' : 'Editar post' ?></h1>

<form method="post" action="<?= htmlspecialchars((string) $formAction, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
    <?= $csrfField ?>

    <div class="mb-3">
        <label for="title" class="form-label">Título</label>
        <input type="text" name="title" id="title" class="form-control" maxlength="200" value="<?= htmlspecialchars((string) ($post['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug <span class="text-muted">(opcional — gerado do título se vazio)</span></label>
        <input type="text" name="slug" id="slug" class="form-control" value="<?= htmlspecialchars((string) ($post['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" pattern="[a-z0-9]+(-[a-z0-9]+)*">
    </div>

    <div class="mb-3">
        <label for="summary" class="form-label">Resumo</label>
        <textarea name="summary" id="summary" class="form-control" rows="2" maxlength="500" required><?= htmlspecialchars((string) ($post['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <div class="mb-3">
        <label for="body_md" class="form-label">Conteúdo (Markdown)</label>
        <textarea name="body_md" id="body_md" class="form-control" rows="14"><?= htmlspecialchars((string) ($post['body_md'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        <div id="cherry-markdown-container" style="height: 600px; margin-top: 0.5rem;"></div>
    </div>

    <div class="mb-3">
        <span class="form-label d-block">Categorias</span>
        <?php if (empty($categories)): ?>
            <p class="text-muted small mb-0">Nenhuma categoria cadastrada.</p>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="categories[]" id="cat-<?= (int) $category['id'] ?>" value="<?= (int) $category['id'] ?>" <?= in_array((int) $category['id'], $selectedIds, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="cat-<?= (int) $category['id'] ?>"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8') ?></label>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select">
                <option value="rascunho" <?= $statusValue === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                <option value="publicar" <?= $statusValue === 'publicar' ? 'selected' : '' ?>>Publicar</option>
                <option value="agendar" <?= $statusValue === 'agendar' ? 'selected' : '' ?>>Agendar</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="published_at" class="form-label">Data/hora (para "Agendar")</label>
            <input type="datetime-local" name="published_at" id="published_at" class="form-control" value="<?= htmlspecialchars((string) $publishedAtValue, ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div class="mb-3">
        <label for="cover_image" class="form-label">Imagem de capa (JPEG, PNG ou WebP)</label>
        <input type="file" name="cover_image" id="cover_image" class="form-control" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($post['cover_image'])): ?>
            <div class="mt-2">
                <span class="text-muted small d-block">Imagem atual:</span>
                <img src="<?= htmlspecialchars((string) $post['cover_image'], ENT_QUOTES, 'UTF-8') ?>" alt="Capa atual" class="img-thumbnail" style="max-width: 200px;">
            </div>
        <?php endif; ?>
    </div>

    <div class="alert alert-info">
        A tradução para o inglês é gerada automaticamente ao salvar este post.
    </div>

    <button type="submit" class="btn btn-primary">Salvar</button>
    <a href="/admin/posts" class="btn btn-outline-secondary">Cancelar</a>
</form>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cherry-markdown@0.11.9/dist/cherry-markdown.min.css">
<script src="https://cdn.jsdelivr.net/npm/cherry-markdown@0.11.9/dist/cherry-markdown.js"></script>
<script>
(function () {
    var textarea = document.getElementById('body_md');
    var form = document.querySelector('form');

    // Fallback: se o Cherry não carregar, o textarea tradicional continua funcionando
    if (typeof Cherry === 'undefined' || !textarea || !form) {
        return;
    }

    // Inicializa o editor visual usando o conteúdo atual do textarea body_md
    var cherry = new Cherry({
        id: 'cherry-markdown-container',
        value: textarea.value,
        editor: {
            // Ao colar HTML (ex.: copiado do Notion), converte automaticamente para Markdown
            convertWhenPaste: true,
            // Nome vazio impede que o textarea interno do Cherry seja enviado junto ao POST
            name: ''
        }
    });

    // Oculta o textarea original: o Cherry passa a ser a interface visual de edição
    textarea.style.display = 'none';

    // Sincroniza o Markdown do editor de volta ao textarea antes do envio (POST tradicional)
    form.addEventListener('submit', function () {
        textarea.value = cherry.getMarkdown();
    });
})();
</script>

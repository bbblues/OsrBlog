<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-4">Alterar senha</h1>

                <form method="post" action="/admin/perfil">
                    <?= $csrfField ?>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Senha atual</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nova senha</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" minlength="8" required>
                        <div class="form-text">Mínimo de 8 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar nova senha</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Alterar senha</button>
                    <a href="/admin" class="btn btn-outline-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>

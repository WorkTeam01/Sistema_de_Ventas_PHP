<?php
$title = 'Nueva contraseña';
$pageCss = BASE_URL . '/css/modules/auth/reset-password.css';
$pageScripts = ['reset-password.js'];

ob_start();
?>
<h5 class="login-box-msg">Nueva contraseña</h5>
<p class="text-muted text-center" style="font-size:14px;">
    Ingresa y confirma tu nueva contraseña.
</p>

<form id="resetForm" action="<?= BASE_URL ?>/auth/reset-password" method="post">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

    <div class="input-group mb-3">
        <label for="password" class="sr-only">Nueva contraseña (requerido)</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Nueva contraseña" autocomplete="new-password" required aria-required="true" minlength="8">
        <div class="input-group-append">
            <button type="button" class="btn btn-default" data-toggle-password="password" aria-label="Mostrar contraseña" aria-pressed="false">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <!-- Indicador de fortaleza -->
    <div class="mb-3">
        <div class="password-strength-bar">
            <div class="password-strength-fill" id="strengthFill"></div>
        </div>
        <small id="strengthText" class="text-muted" aria-live="polite"></small>
    </div>

    <div class="input-group mb-3">
        <label for="password_confirm" class="sr-only">Confirmar contraseña (requerido)</label>
        <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Confirmar contraseña" autocomplete="new-password" required aria-required="true">
        <div class="input-group-append">
            <button type="button" class="btn btn-default" data-toggle-password="password_confirm" aria-label="Mostrar contraseña" aria-pressed="false">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-custom btn-block" id="btnReset">
        GUARDAR CONTRASEÑA
    </button>
    <div id="formStatus" class="sr-only" aria-live="polite"></div>
</form>

<p class="mt-3 mb-0 text-center">
    <a href="<?= BASE_URL ?>/auth" class="text-muted" style="font-size:14px;">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>Volver al inicio de sesión
    </a>
</p>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/auth.php';

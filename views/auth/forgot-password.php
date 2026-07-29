<?php
$title = 'Recuperar contraseña';
$pageScripts = ['forgot-password.js'];

ob_start();
?>
<h5 class="login-box-msg">Recuperar contraseña</h5>
<p class="text-muted text-center" style="font-size:14px;">
    Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
</p>

<form id="forgotForm" action="<?= BASE_URL ?>/auth/forgot-password" method="post">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <div class="input-group mb-3">
        <label for="email" class="sr-only">Correo electrónico (requerido)</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="Correo electrónico" autocomplete="email" required aria-required="true">
        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-envelope" aria-hidden="true"></span>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-custom btn-block" id="btnSend">
        ENVIAR ENLACE
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

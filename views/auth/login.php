<?php
$title = 'Iniciar sesión';
$extraCss = 'icheck';
$pageScripts = ['login.js'];

ob_start();
?>
<h5 class="login-box-msg">Iniciar Sesión</h5>

<form id="loginForm" action="<?= BASE_URL ?>/auth/login" method="post">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
    <div class="input-group">
        <label for="email" class="sr-only">Correo electrónico (requerido)</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="Correo electrónico" autocomplete="email" required aria-required="true">
        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-envelope" aria-hidden="true"></span>
            </div>
        </div>
    </div>
    <div class="input-group">
        <label for="password_user" class="sr-only">Contraseña (requerido)</label>
        <input type="password" id="password_user" name="password_user" class="form-control" placeholder="Contraseña" autocomplete="current-password" required aria-required="true">
        <div class="input-group-append">
            <button type="button" class="btn btn-default" data-toggle-password="password_user" aria-label="Mostrar contraseña" aria-pressed="false">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="icheck-primary mb-3">
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember">Recordarme</label>
    </div>
    <button type="submit" class="btn btn-custom btn-block" id="btnLogin">
        INGRESAR
    </button>
    <div id="formStatus" class="sr-only" aria-live="polite"></div>
</form>

<p class="mt-3 mb-0 text-center">
    <a href="<?= BASE_URL ?>/auth/forgot-password" class="text-muted" style="font-size:14px;">
        ¿Olvidaste tu contraseña?
    </a>
</p>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/auth.php';

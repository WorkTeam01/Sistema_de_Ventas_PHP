<?php

/**
 * Layout compartido para las vistas de autenticación (login, forgot-password, reset-password).
 * Ubicado junto al resto de layouts del proyecto (views/layouts/).
 * Variables esperadas: $title (string), $content (string HTML), $pageScripts (string[] opcional,
 * nombres de archivo dentro de js/modules/auth/), $pageCss (string opcional, ruta absoluta a un CSS extra).
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Tema (claro/oscuro) aplicado antes del primer paint para evitar parpadeo -->
    <script>
        (function() {
            var saved = localStorage.getItem('auth-theme');
            var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <!-- Google Font: Source Sans Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/fontawesome/all.min.css">
    <?php if (isset($extraCss) && $extraCss === 'icheck'): ?>
        <!-- icheck bootstrap -->
        <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/bootstrap/icheck-bootstrap.min.css">
    <?php endif; ?>
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/adminlte/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/plugins/sweetalert2/sweetalert2.min.css">
    <!-- Custom Auth Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/modules/auth/login.css">
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= $pageCss ?>">
    <?php endif; ?>
    <!-- Icono del sitio -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/logo.png">

    <script src="<?= BASE_URL ?>/js/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?= BASE_URL ?>/js/core/sweetalert-utils.js"></script>
</head>

<body class="hold-transition login-page">
    <button type="button" id="themeToggle" class="theme-toggle-btn" aria-label="Cambiar a modo oscuro" aria-pressed="false">
        <i class="fas fa-moon" aria-hidden="true"></i>
    </button>

    <div class="login-box">
        <div class="login-logo">
            <img src="<?= BASE_URL ?>/img/logo_2.png" class="img-circle" width="150" height="150" alt="Logo Sistema de Ventas">
        </div>
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="#" class="h1"><b>SISTEMA DE</b> VENTAS</a>
            </div>
            <div class="card-body">
                <?= $content ?>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="<?= BASE_URL ?>/js/lib/jquery/jquery.min.js"></script>
    <!-- Bootstrap 4 -->
    <script src="<?= BASE_URL ?>/js/lib/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- AdminLTE App -->
    <script src="<?= BASE_URL ?>/js/lib/adminlte/adminlte.min.js"></script>
    <!-- jQuery Validate -->
    <script src="<?= BASE_URL ?>/js/lib/jquery/jquery.validate.min.js"></script>
    <script src="<?= BASE_URL ?>/js/lib/jquery/messages_es.min.js"></script>
    <!-- Tema (propio de auth) -->
    <script src="<?= BASE_URL ?>/js/modules/auth/theme-toggle.js"></script>
    <!-- Mostrar/ocultar contraseña (global, en core) -->
    <script src="<?= BASE_URL ?>/js/core/password-toggle.js"></script>
    <!-- Utilidades compartidas de formularios de auth -->
    <script src="<?= BASE_URL ?>/js/core/auth-form-utils.js"></script>
    <?php foreach ($pageScripts ?? [] as $src): ?>
        <script src="<?= BASE_URL ?>/js/modules/auth/<?= $src ?>"></script>
    <?php endforeach; ?>
</body>

</html>
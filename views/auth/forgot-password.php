<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/dist/css/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/sweetalert2.min.css">
    <!-- Custom Auth Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/modules/auth/login.css">
    <!-- Icono del sitio -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/logo.png">

    <script src="<?= BASE_URL ?>/js/sweetalert2.min.js"></script>
    <script src="<?= BASE_URL ?>/js/core/sweetalert-utils.js"></script>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <?php if ($mensaje): ?>
            <script>
                showToast(<?= json_encode($icono) ?>, <?= json_encode($mensaje) ?>);
            </script>
        <?php endif; ?>

        <div class="login-logo">
            <img src="<?= BASE_URL ?>/img/logo_2.png" class="img-circle" width="150" height="150" alt="Logo Sistema de Ventas">
        </div>
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="#" class="h1"><b>SISTEMA DE</b> VENTAS</a>
            </div>
            <div class="card-body">
                <h5 class="login-box-msg">Recuperar contraseña</h5>
                <p class="text-muted text-center" style="font-size:14px;">
                    Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
                </p>

                <form id="forgotForm" action="<?= BASE_URL ?>/auth/forgot-password" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="input-group mb-3">
                        <input type="email" id="email" name="email" class="form-control" placeholder="Correo electrónico" autocomplete="email">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-custom btn-block" id="btnSend">
                        ENVIAR ENLACE
                    </button>
                </form>

                <p class="mt-3 mb-0 text-center">
                    <a href="<?= BASE_URL ?>/auth" class="text-muted" style="font-size:14px;">
                        <i class="fas fa-arrow-left mr-1"></i>Volver al inicio de sesión
                    </a>
                </p>
            </div>
        </div>

        <!-- jQuery -->
        <script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>
        <!-- Bootstrap 4 -->
        <script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <!-- AdminLTE App -->
        <script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/dist/js/adminlte.min.js"></script>
        <!-- jQuery Validate -->
        <script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/jquery-validation/jquery.validate.min.js"></script>
        <script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/jquery-validation/localization/messages_es.min.js"></script>
        <!-- Forgot Password JS -->
        <script src="<?= BASE_URL ?>/js/modules/auth/forgot-password.js"></script>
</body>

</html>
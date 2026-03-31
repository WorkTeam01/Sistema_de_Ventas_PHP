<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
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
        <?php if ($respuesta): ?>
            <script>showToast(<?= json_encode('error') ?>, <?= json_encode($respuesta) ?>);</script>
        <?php endif; ?>

        <div class="login-logo">
            <img src="<?= BASE_URL ?>/img/logo.png" class="img-circle" width="150" height="150" alt="Logo Sistema de Ventas">
        </div>
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="#" class="h1"><b>SISTEMA DE</b> VENTAS</a>
            </div>
            <div class="card-body">
                <h5 class="login-box-msg">Iniciar Sesión</h5>

                <form id="loginForm" action="<?= BASE_URL ?>/auth/login" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <div class="input-group">
                        <input type="email" id="email" name="email" class="form-control" placeholder="Correo electrónico" autocomplete="email">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group">
                        <input type="password" id="password_user" name="password_user" class="form-control" placeholder="Contraseña" autocomplete="current-password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-custom btn-block" id="btnLogin">
                        INGRESAR
                    </button>
                </form>
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
        <!-- Login JS -->
        <script src="<?= BASE_URL ?>/js/modules/auth/login.js"></script>
</body>

</html>

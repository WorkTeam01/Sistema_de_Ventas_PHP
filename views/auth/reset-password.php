<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva contraseña</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/fontawesome/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/adminlte/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/plugins/sweetalert2/sweetalert2.min.css">
    <!-- Custom Auth Style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/modules/auth/login.css">
    <!-- Reset Password CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/modules/auth/reset-password.css">
    <!-- Icono del sitio -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/logo.png">

    <script src="<?= BASE_URL ?>/js/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?= BASE_URL ?>/js/core/sweetalert-utils.js"></script>
</head>

<body class="hold-transition login-page">
    <div class="login-box">
        <div class="login-logo">
            <img src="<?= BASE_URL ?>/img/logo_2.png" class="img-circle" width="150" height="150" alt="Logo Sistema de Ventas">
        </div>
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                <a href="#" class="h1"><b>SISTEMA DE</b> VENTAS</a>
            </div>
            <div class="card-body">
                <h5 class="login-box-msg">Nueva contraseña</h5>
                <p class="text-muted text-center" style="font-size:14px;">
                    Ingresa y confirma tu nueva contraseña.
                </p>

                <form id="resetForm" action="<?= BASE_URL ?>/auth/reset-password" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="input-group mb-3">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Nueva contraseña" autocomplete="new-password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Indicador de fortaleza -->
                    <div class="mb-3">
                        <div class="password-strength-bar">
                            <div class="password-strength-fill" id="strengthFill"></div>
                        </div>
                        <small id="strengthText" class="text-muted"></small>
                    </div>

                    <div class="input-group mb-3">
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Confirmar contraseña" autocomplete="new-password">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default" id="toggleConfirm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-custom btn-block" id="btnReset">
                        GUARDAR CONTRASEÑA
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
        <script src="<?= BASE_URL ?>/js/lib/jquery/jquery.min.js"></script>
        <!-- Bootstrap 4 -->
        <script src="<?= BASE_URL ?>/js/lib/bootstrap/bootstrap.bundle.min.js"></script>
        <!-- AdminLTE App -->
        <script src="<?= BASE_URL ?>/js/lib/adminlte/adminlte.min.js"></script>
        <!-- jQuery Validate -->
        <script src="<?= BASE_URL ?>/js/lib/jquery/jquery.validate.min.js"></script>
        <script src="<?= BASE_URL ?>/js/lib/jquery/messages_es.min.js"></script>
        <!-- Reset Password JS -->
        <script src="<?= BASE_URL ?>/js/modules/auth/reset-password.js"></script>
</body>

</html>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Enlace de restablecimiento (modo dev)</title>

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
</head>

<body class="hold-transition login-page">
    <div class="login-box" style="width: 500px;">
        <div class="login-logo">
            <img src="<?= BASE_URL ?>/img/logo_2.png" class="img-circle" width="150" height="150" alt="Logo Sistema de Ventas">
        </div>
        <div class="card card-outline card-warning">
            <div class="card-header text-center">
                <a href="#" class="h1"><b>MODO</b> DESARROLLO</a>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>Solo visible con APP_DEBUG=true.</strong>
                    En producción solo se envía el correo.
                </div>

                <?php if ($emailSent): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-1"></i>
                        Email enviado correctamente a <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle mr-1"></i>
                        <strong>El email no pudo enviarse.</strong> Verifica las credenciales MAIL_* en tu <code>.env</code>.
                    </div>
                <?php endif; ?>

                <h6 class="text-muted mb-2">Enlace de restablecimiento (válido 60 min):</h6>
                <div class="input-group mb-3">
                    <input type="text" id="resetLink" class="form-control" value="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" readonly>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary" id="btnCopy" title="Copiar enlace">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>

                <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-custom btn-block">
                    <i class="fas fa-key mr-1"></i> Ir al formulario de restablecimiento
                </a>

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
        <script>
            document.getElementById('btnCopy').addEventListener('click', function () {
                const input = document.getElementById('resetLink');
                input.select();
                document.execCommand('copy');
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => { this.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
            });
        </script>
</body>

</html>
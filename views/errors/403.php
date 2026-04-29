<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = '';
$icono = 'error';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $icono = $_SESSION['icono'] ?? 'error';
    unset($_SESSION['mensaje'], $_SESSION['icono']);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Acceso denegado</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/fontawesome/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/adminlte/adminlte.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/plugins/sweetalert2/sweetalert2.min.css">
    <script src="<?= BASE_URL ?>/js/plugins/sweetalert2/sweetalert2.min.js"></script>
    <!-- Icono del sitio -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/logo.png">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        .error-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f4f6f9;
            text-align: center;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            line-height: 1;
            color: #dc3545;
            text-shadow: 2px 4px 0 rgba(0,0,0,.1);
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 1rem 0 0.5rem;
            color: #343a40;
        }
        .error-description {
            color: #6c757d;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="error-wrapper">
        <div>
            <div class="error-code">403</div>
            <p class="error-title">
                <i class="fas fa-ban text-danger mr-2"></i>Acceso denegado
            </p>
            <p class="error-description">No tienes permisos para acceder a esta página.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-danger btn-lg">
                <i class="fas fa-home mr-1"></i> Volver al inicio
            </a>
        </div>
    </div>

    <?php if ($mensaje): ?>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        Toast.fire({
            icon: "<?= htmlspecialchars($icono, ENT_QUOTES, 'UTF-8') ?>",
            title: "<?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>"
        });
    </script>
    <?php endif; ?>
</body>

</html>

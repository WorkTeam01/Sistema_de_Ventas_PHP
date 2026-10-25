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
    <script src="<?= BASE_URL ?>/js/core/sweetalert-utils.js?v=<?= APP_VERSION ?>"></script>
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
            color: #495057;
            margin-bottom: 2rem;
        }
        body.dark-mode .btn-danger {
            background-color: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>

<body>
    <main class="error-wrapper">
        <div>
            <div class="error-code" aria-hidden="true">403</div>
            <h1 class="error-title">
                <i class="fas fa-ban text-danger mr-2" aria-hidden="true"></i>Acceso denegado
            </h1>
            <p class="error-description">No tienes permisos para acceder a esta página.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-danger btn-lg">
                <i class="fas fa-home mr-1"></i> Volver al inicio
            </a>
        </div>
    </main>

    <?php if ($mensaje): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const type = <?= json_encode($icono) ?>;
            const msg = <?= json_encode($mensaje) ?>;
            if (typeof ToastUtils !== 'undefined' && typeof ToastUtils[type] === 'function') {
                ToastUtils[type](msg);
            }
        });
    </script>
    <?php endif; ?>
</body>

</html>

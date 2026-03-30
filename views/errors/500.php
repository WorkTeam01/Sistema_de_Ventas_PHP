<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Error interno del servidor</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/dist/css/adminlte.min.css">
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
            <div class="error-code">500</div>
            <p class="error-title">
                <i class="fas fa-exclamation-circle text-danger mr-2"></i>Error interno del servidor
            </p>
            <p class="error-description">Estamos trabajando para solucionar el problema.</p>
            <a href="<?= BASE_URL ?>/" class="btn btn-danger btn-lg">
                <i class="fas fa-home mr-1"></i> Volver al inicio
            </a>
        </div>
    </div>
</body>

</html>

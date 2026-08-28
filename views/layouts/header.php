<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Ventas</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/fontawesome/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/lib/adminlte/adminlte.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/core/ui-components.css?v=<?= APP_VERSION ?>">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/plugins/sweetalert2/sweetalert2.min.css">
    <script src="<?= BASE_URL ?>/js/plugins/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?= BASE_URL ?>/js/core/sweetalert-utils.js?v=<?= APP_VERSION ?>"></script>
    <?php if (in_array('datatable', $assets ?? [])) : ?>
        <!-- DataTables -->
        <link rel="stylesheet"
            href="<?= BASE_URL ?>/css/plugins/datatables/dataTables.bootstrap4.min.css">
        <link rel="stylesheet"
            href="<?= BASE_URL ?>/css/plugins/datatables/responsive.bootstrap4.min.css">
        <link rel="stylesheet"
            href="<?= BASE_URL ?>/css/plugins/datatables/buttons.bootstrap4.min.css">
    <?php endif; ?>
    <?php if (in_array('select2', $assets ?? [])) : ?>
        <!-- Select2 -->
        <link rel="stylesheet" href="<?= BASE_URL ?>/css/plugins/select2/select2.min.css">
        <link rel="stylesheet"
            href="<?= BASE_URL ?>/css/plugins/select2/select2-bootstrap4.min.css">
    <?php endif; ?>
    <?php if (in_array('chart', $assets ?? [])) : ?>
        <!-- Chart.js -->
        <link rel="stylesheet" href="<?= BASE_URL ?>/css/plugins/chart/Chart.min.css">
    <?php endif; ?>
    <!-- jQuery -->
    <script src="<?= BASE_URL ?>/js/lib/jquery/jquery.min.js"></script>
    <!-- Icono del sitio -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/logo.png">

    <!-- Page specific styles -->
    <?php if (isset($pageStyles)): ?>
        <?php foreach ($pageStyles as $style): ?>
            <link rel="stylesheet" href="<?= BASE_URL; ?><?= $style ?>?v=<?= APP_VERSION ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <script>
        // Aplicar la configuración del tema instantáneamente para evitar parpadeo blanco (FOUC)
        var savedTheme = localStorage.getItem('controlSidebarSettings');
        if (savedTheme) {
            try {
                var settings = JSON.parse(savedTheme);
                if (settings && settings.body_class) {
                    // Evitar que el panel de control se quede abierto si se guardó por error
                    var cleanBodyClass = settings.body_class.replace('control-sidebar-slide-open', '').trim();
                    document.body.className = cleanBodyClass;
                }
            } catch (e) {}
        }
    </script>
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light" aria-label="Barra superior">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Abrir o cerrar menú lateral"><i class="fas fa-bars"></i></a>
                </li>
                <!-- Logo visible solo en móvil -->
                <li class="nav-item d-sm-none">
                    <a href="<?= BASE_URL ?>" class="nav-link d-flex align-items-center">
                        <img src="<?= BASE_URL ?>/img/logo_2.png" alt="Logo Hielo Cambita"
                            class="img-circle" style="width: 25px; height: 25px; margin-right: 8px;">
                        <span class="brand-text inter-brand-text">Sistema de Ventas</span>
                    </a>
                </li>
            </ul>
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button" aria-label="Alternar pantalla completa">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button" aria-label="Abrir panel de configuración">
                        <i class="fas fa-th-large"></i>
                    </a>
                </li>

                <!-- Separador visible solo en desktop -->
                <li class="nav-item d-none d-md-block">
                    <div class="border-left mt-2" style="height: 24px;"></div>
                </li>

                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Menú de usuario: <?= htmlspecialchars($nombres_sesion ?? '') ?>">
                        <span class="d-none d-md-inline"><?= htmlspecialchars($nombres_sesion ?? '') ?></span>
                        <i class="fas fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <li class="user-header">
                            <img src="<?= BASE_URL ?>/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                            <p>
                                <?= htmlspecialchars($nombres_sesion ?? '') ?>
                                <small><?= htmlspecialchars($rol_sesion ?? '') ?></small>
                            </p>
                        </li>
                        <li class="user-footer">
                            <a href="<?= BASE_URL ?>/profile" class="btn btn-default btn-flat">Ver perfil</a>
                            <form action="<?= BASE_URL ?>/auth/logout" method="post" class="float-right">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Auth::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-default btn-flat">Cerrar sesión</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <?php require __DIR__ . '/partials/_sidebar.php'; ?>
        <main>
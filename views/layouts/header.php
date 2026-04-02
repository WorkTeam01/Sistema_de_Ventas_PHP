<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Ventas</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/fontawesome-free/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/core/ui-components.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/sweetalert2.min.css">
    <script src="<?= BASE_URL ?>/js/sweetalert2.min.js"></script>
    <script src="<?= BASE_URL ?>/js/core/sweetalert-utils.js"></script>
    <?php if (in_array('datatable', $assets ?? [])) : ?>
        <!-- DataTables -->
        <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <?php endif; ?>
    <?php if (in_array('select2', $assets ?? [])) : ?>
        <!-- Select2 -->
        <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/select2/css/select2.min.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <?php endif; ?>
    <!-- jQuery -->
    <script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>
    <!-- Icono del sitio -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/logo.png">

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
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
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
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                        <i class="fas fa-th-large"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <?php require __DIR__ . '/partials/_sidebar.php'; ?>
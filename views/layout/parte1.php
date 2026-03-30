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
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/sweetalert2.min.css">
    <script src="<?= BASE_URL ?>/js/sweetalert2.min.js"></script>
    <!-- DataTables -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    <!-- jQuery -->
    <script src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/plugins/jquery/jquery.min.js"></script>
    <!-- Icono del sitio -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/img/logo.png">
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
                        <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo Hielo Cambita"
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

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-2">
            <!-- Brand Logo -->
            <a href="<?= BASE_URL ?>" class="brand-link">
                <img src="<?= BASE_URL ?>/img/logo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-1" style="opacity: .8">
                <span class="brand-text font-weight-light">Pagina principal</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block"><?= $nombres_sesion; ?></a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <!-- Modulo de usuarios -->
                        <?php if ($rol_sesion == 'Administrador'): ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>
                                        Usuarios
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/users" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de usuarios</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/users/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear usuarios</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Modulo de roles-->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-address-card"></i>
                                    <p>
                                        Roles
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/roles" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de roles</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/roles/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear rol</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Comprador' || $rol_sesion == 'Vendedor') : ?>
                            <!-- Modulo de categorias-->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-tags"></i>
                                    <p>
                                        Categorias
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/categories" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de categorias</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/categories/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear categoría</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- Modulo de almacen (MVC) -->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-boxes"></i>
                                    <p>
                                        Almacén
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/products" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de productos</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/products/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Registrar producto</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Comprador') : ?>
                            <!-- Modulo de proveedores -->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-truck"></i>
                                    <p>
                                        Proveedores
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/suppliers" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de proveedores</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/suppliers/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear proveedor</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <!-- Modulo de compras -->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-shopping-cart"></i>
                                    <p>Compras <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/purchases" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de compras</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/purchases/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Registrar compra</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Vendedor') : ?>
                            <!-- Modulo de ventas -->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-shopping-basket"></i>
                                    <p>
                                        Ventas
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/sales" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de ventas</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/sales/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear venta</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Modulo de clientes -->
                            <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Vendedor') : ?>
                                <li class="nav-item">
                                    <a href="#" class="nav-link active">
                                        <i class="nav-icon fas fa-user-friends"></i>
                                        <p>
                                            Clientes
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        <li class="nav-item">
                                            <a href="<?= BASE_URL ?>/clients" class="nav-link">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Lista de clientes</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="<?= BASE_URL ?>/clients/create" class="nav-link">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Crear cliente</p>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/auth/logout" class="nav-link bg-danger">
                                    <i class="nav-icon fas fa-door-closed"></i>
                                    <p>Cerrar sesión</p>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>
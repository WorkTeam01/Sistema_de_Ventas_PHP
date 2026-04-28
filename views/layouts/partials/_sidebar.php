<?php
$nombres_sesion = $nombres_sesion ?? '';
$rol_sesion     = $rol_sesion ?? '';

// Extraer el path relativo a la base: "/users/create", "/products", etc.
$_basePath    = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
$_currentPath = substr(strtok($_SERVER['REQUEST_URI'], '?'), strlen($_basePath));
if ($_currentPath === '' || $_currentPath === false) $_currentPath = '/';

// — Módulo Usuarios —
$usersTree         = str_starts_with($_currentPath, '/users');
$usersListActive   = $usersTree && !str_starts_with($_currentPath, '/users/create');
$usersCreateActive = $_currentPath === '/users/create';

// — Módulo Roles —
$rolesActive = str_starts_with($_currentPath, '/roles');

// — Módulo Categorías —
$categoriesActive = str_starts_with($_currentPath, '/categories');

// — Módulo Almacén —
$productsTree         = str_starts_with($_currentPath, '/products');
$productsListActive   = $productsTree && !str_starts_with($_currentPath, '/products/create');
$productsCreateActive = $_currentPath === '/products/create';

// — Módulo Proveedores —
$suppliersActive = str_starts_with($_currentPath, '/suppliers');

// — Módulo Compras —
$purchasesTree         = str_starts_with($_currentPath, '/purchases');
$purchasesListActive   = $purchasesTree && !str_starts_with($_currentPath, '/purchases/create');
$purchasesCreateActive = $_currentPath === '/purchases/create';

// — Módulo Ventas —
$salesTree         = str_starts_with($_currentPath, '/sales');
$salesListActive   = $salesTree && !str_starts_with($_currentPath, '/sales/create');
$salesCreateActive = $_currentPath === '/sales/create';

// — Módulo Clientes —
$clientsActive = str_starts_with($_currentPath, '/clients');

$isAdmin  = $rol_sesion === 'Administrador';
$isSeller = $rol_sesion === 'Vendedor';
$isBuyer  = $rol_sesion === 'Comprador';

// Helpers para emitir clases limpias
$li   = fn(bool $on) => $on ? ' menu-open' : '';
$link = fn(bool $on) => $on ? ' active' : '';
$tree = fn(bool $on) => $on ? ' active menu-open' : '';
?>
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-2">
    <!-- Brand Logo -->
    <a href="<?= BASE_URL ?>" class="brand-link">
        <img src="<?= BASE_URL ?>/img/logo_2.png" alt="Logo" loading="eager"
             class="brand-image img-circle elevation-1" style="opacity: .8">
        <span class="brand-text font-weight-light">Pagina principal</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg"
                     class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="<?= BASE_URL ?>/profile" class="d-block"><?= htmlspecialchars($nombres_sesion) ?></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <?php if ($isAdmin): ?>
                    <!-- Módulo Usuarios -->
                    <li class="nav-item<?= $li($usersTree) ?>">
                        <a href="#" class="nav-link<?= $tree($usersTree) ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/users" class="nav-link<?= $link($usersListActive) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lista de usuarios</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/users/create" class="nav-link<?= $link($usersCreateActive) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear usuario</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Módulo Roles -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/roles" class="nav-link<?= $link($rolesActive) ?>">
                            <i class="nav-icon fas fa-address-card"></i>
                            <p>Roles</p>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isBuyer || $isSeller): ?>
                    <!-- Módulo Categorías -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/categories" class="nav-link<?= $link($categoriesActive) ?>">
                            <i class="nav-icon fas fa-tags"></i>
                            <p>Categorías</p>
                        </a>
                    </li>

                    <!-- Módulo Almacén -->
                    <li class="nav-item<?= $li($productsTree) ?>">
                        <a href="#" class="nav-link<?= $tree($productsTree) ?>">
                            <i class="nav-icon fas fa-boxes"></i>
                            <p>Almacén <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/products" class="nav-link<?= $link($productsListActive) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lista de productos</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/products/create" class="nav-link<?= $link($productsCreateActive) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Registrar producto</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isBuyer): ?>
                    <!-- Módulo Proveedores -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/suppliers" class="nav-link<?= $link($suppliersActive) ?>">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>Proveedores</p>
                        </a>
                    </li>

                    <!-- Módulo Compras -->
                    <li class="nav-item<?= $li($purchasesTree) ?>">
                        <a href="#" class="nav-link<?= $tree($purchasesTree) ?>">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>Compras <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/purchases" class="nav-link<?= $link($purchasesListActive) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lista de compras</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/purchases/create" class="nav-link<?= $link($purchasesCreateActive) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Registrar compra</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isSeller): ?>
                    <!-- Módulo Ventas -->
                    <li class="nav-item<?= $li($salesTree) ?>">
                        <a href="#" class="nav-link<?= $tree($salesTree) ?>">
                            <i class="nav-icon fas fa-shopping-basket"></i>
                            <p>Ventas <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/sales" class="nav-link<?= $link($salesListActive) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Lista de ventas</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/sales/create" class="nav-link<?= $link($salesCreateActive) ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Crear venta</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Módulo Clientes -->
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/clients" class="nav-link<?= $link($clientsActive) ?>">
                            <i class="nav-icon fas fa-user-tag"></i>
                            <p>Clientes</p>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Cerrar sesión (todos los roles) -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/auth/logout" class="nav-link bg-danger">
                        <i class="nav-icon fas fa-door-open"></i>
                        <p>Cerrar sesión</p>
                    </a>
                </li>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

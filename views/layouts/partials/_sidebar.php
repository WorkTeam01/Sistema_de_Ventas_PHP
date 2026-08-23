<?php
// Extraer el path relativo a la base: "/users/create", "/products", etc.
$_basePath    = rtrim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
$_currentPath = substr(strtok($_SERVER['REQUEST_URI'], '?'), strlen($_basePath));
if ($_currentPath === '' || $_currentPath === false) $_currentPath = '/';

// — Dashboard —
$dashboardActive = $_currentPath === '/';

// — Módulo Usuarios —
$usersActive = str_starts_with($_currentPath, '/users');

// — Módulo Roles —
$rolesActive = str_starts_with($_currentPath, '/roles');
$permissionsActive = str_starts_with($_currentPath, '/permissions');

// — Módulo Categorías —
$categoriesActive = str_starts_with($_currentPath, '/categories');

// — Módulo Almacén —
$productsActive = str_starts_with($_currentPath, '/products');

// — Módulo Proveedores —
$suppliersActive = str_starts_with($_currentPath, '/suppliers');

// — Módulo Compras —
$purchasesActive = str_starts_with($_currentPath, '/purchases');

// — Módulo Clientes —
$clientsActive = str_starts_with($_currentPath, '/clients');

// — Módulo Ventas —
$salesActive = str_starts_with($_currentPath, '/sales');

// — Módulo Activity Log —
$activityLogActive = str_starts_with($_currentPath, '/activity-log');

// — Módulo Inventario —
$inventoryActive = str_starts_with($_currentPath, '/inventory');

// — Módulo Reportes —
$reportsActive = str_starts_with($_currentPath, '/reports');

$can      = $can ?? [];
$isAdmin  = $can['is_superadmin'] ?? false;
$isSeller = ($can['view_sales'] ?? false) && !$isAdmin;
$isBuyer  = ($can['view_purchases'] ?? false) && !$isAdmin && !$isSeller;

$link = fn(bool $on) => $on ? ' active' : '';
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
        <!-- Sidebar Menu -->
        <nav class="mt-2" aria-label="Menú principal">
            <ul class="nav nav-pills nav-sidebar nav-compact flex-column" data-widget="treeview" data-accordion="false">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/" class="nav-link<?= $link($dashboardActive) ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <?php if ($isAdmin): ?>
                    <!-- SECCIÓN: Administración -->
                    <li class="nav-header">ADMINISTRACIÓN</li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/users" class="nav-link<?= $link($usersActive) ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/roles" class="nav-link<?= $link($rolesActive) ?>">
                            <i class="nav-icon fas fa-address-card"></i>
                            <p>Roles</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/permissions" class="nav-link<?= $link($permissionsActive) ?>">
                            <i class="nav-icon fas fa-key"></i>
                            <p>Permisos</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/activity-log" class="nav-link<?= $link($activityLogActive) ?>">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Auditoría</p>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isBuyer || $isSeller): ?>
                    <!-- SECCIÓN: Inventario -->
                    <li class="nav-header">INVENTARIO</li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/categories" class="nav-link<?= $link($categoriesActive) ?>">
                            <i class="nav-icon fas fa-tags"></i>
                            <p>Categorías</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/products" class="nav-link<?= $link($productsActive) ?>">
                            <i class="nav-icon fas fa-boxes"></i>
                            <p>Almacén</p>
                        </a>
                    </li>

                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/inventory" class="nav-link<?= $link($inventoryActive) ?>">
                                <i class="nav-icon fas fa-warehouse"></i>
                                <p>Inventario</p>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($can['view_reports'] ?? false): ?>
                    <!-- SECCIÓN: Reportes -->
                    <li class="nav-header">REPORTES</li>

                    <?php if ($can['view_sales_report'] ?? false): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/reports/sales" class="nav-link<?= $link(str_starts_with($_currentPath, '/reports/sales')) ?>">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Ventas</p>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($can['view_top_products_report'] ?? false): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/reports/top-products" class="nav-link<?= $link(str_starts_with($_currentPath, '/reports/top-products')) ?>">
                                <i class="nav-icon fas fa-trophy"></i>
                                <p>Top Productos</p>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($can['view_purchases_report'] ?? false): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/reports/purchases" class="nav-link<?= $link(str_starts_with($_currentPath, '/reports/purchases')) ?>">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>Compras</p>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($can['view_clients_report'] ?? false): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/reports/clients" class="nav-link<?= $link(str_starts_with($_currentPath, '/reports/clients')) ?>">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Clientes</p>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($isAdmin || $isBuyer): ?>
                    <!-- SECCIÓN: Compras -->
                    <li class="nav-header">COMPRAS</li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/suppliers" class="nav-link<?= $link($suppliersActive) ?>">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>Proveedores</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/purchases" class="nav-link<?= $link($purchasesActive) ?>">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>Compras</p>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($isAdmin || $isSeller): ?>
                    <!-- SECCIÓN: Ventas -->
                    <li class="nav-header">VENTAS</li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/clients" class="nav-link<?= $link($clientsActive) ?>">
                            <i class="nav-icon fas fa-user-tag"></i>
                            <p>Clientes</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/sales" class="nav-link<?= $link($salesActive) ?>">
                            <i class="nav-icon fas fa-shopping-basket"></i>
                            <p>Ventas</p>
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
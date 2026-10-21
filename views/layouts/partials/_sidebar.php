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
$documentsActive = str_starts_with($_currentPath, '/documents');

// — Módulo Clientes —
$clientsActive = str_starts_with($_currentPath, '/clients');

// — Módulo Ventas —
$salesActive = str_starts_with($_currentPath, '/sales');

// — Módulo Devoluciones —
$returnsActive = str_starts_with($_currentPath, '/returns');

// — Módulo Métodos de pago —
$paymentMethodsActive = str_starts_with($_currentPath, '/payment-methods');

// — Módulo Activity Log —
$activityLogActive = str_starts_with($_currentPath, '/activity-log');

// — Módulo Inventario —
$inventoryActive = str_starts_with($_currentPath, '/inventory');

// — Módulo Reportes —
$reportsActive = str_starts_with($_currentPath, '/reports');

$can      = $can ?? [];
$has = fn(string $slug) => (bool)($can[$slug] ?? false);

$showAdminSection     = $has('manage_users') || $has('manage_roles') || $has('view_activity_log');
$showInventorySection = $has('view_categories') || $has('view_products') || $has('manage_inventory');
$showPurchasesSection = $has('view_suppliers') || $has('view_purchases');
$showSalesSection     = $has('view_clients') || $has('view_sales') || $has('manage_payment_methods');

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

                <?php if ($showAdminSection): ?>
                    <!-- SECCIÓN: Administración -->
                    <li class="nav-header">ADMINISTRACIÓN</li>

                    <?php if ($has('manage_users')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/users" class="nav-link<?= $link($usersActive) ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Usuarios</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($has('manage_roles')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/roles" class="nav-link<?= $link($rolesActive) ?>">
                            <i class="nav-icon fas fa-address-card"></i>
                            <p>Roles</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($has('manage_roles')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/permissions" class="nav-link<?= $link($permissionsActive) ?>">
                            <i class="nav-icon fas fa-key"></i>
                            <p>Permisos</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($has('view_activity_log')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/activity-log" class="nav-link<?= $link($activityLogActive) ?>">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Auditoría</p>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($showInventorySection): ?>
                    <!-- SECCIÓN: Inventario -->
                    <li class="nav-header">INVENTARIO</li>

                    <?php if ($has('view_categories')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/categories" class="nav-link<?= $link($categoriesActive) ?>">
                            <i class="nav-icon fas fa-tags"></i>
                            <p>Categorías</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($has('view_products')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/products" class="nav-link<?= $link($productsActive) ?>">
                            <i class="nav-icon fas fa-boxes"></i>
                            <p>Almacén</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($has('manage_inventory')): ?>
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

                <?php if ($showPurchasesSection): ?>
                    <!-- SECCIÓN: Compras -->
                    <li class="nav-header">COMPRAS</li>

                    <?php if ($has('view_suppliers')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/suppliers" class="nav-link<?= $link($suppliersActive) ?>">
                            <i class="nav-icon fas fa-truck"></i>
                            <p>Proveedores</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($has('view_purchases')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/purchases" class="nav-link<?= $link($purchasesActive) ?>">
                            <i class="nav-icon fas fa-shopping-cart"></i>
                            <p>Compras</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($has('view_purchases')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/documents?tipo=orden_compra" class="nav-link<?= $link($documentsActive && ($_GET['tipo'] ?? '') === 'orden_compra') ?>">
                            <i class="nav-icon fas fa-clipboard-list"></i>
                            <p>Órdenes de compra</p>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($showSalesSection): ?>
                    <!-- SECCIÓN: Ventas -->
                    <li class="nav-header">VENTAS</li>

                    <?php if ($has('view_clients')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/clients" class="nav-link<?= $link($clientsActive) ?>">
                            <i class="nav-icon fas fa-user-tag"></i>
                            <p>Clientes</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($has('view_sales')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/sales" class="nav-link<?= $link($salesActive) ?>">
                            <i class="nav-icon fas fa-shopping-basket"></i>
                            <p>Ventas</p>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($has('view_sales')): ?>
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/documents?tipo=cotizacion" class="nav-link<?= $link($documentsActive && ($_GET['tipo'] ?? '') === 'cotizacion') ?>">
                            <i class="nav-icon fas fa-file-alt"></i>
                            <p>Cotizaciones</p>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($has('view_sales')): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/returns" class="nav-link<?= $link($returnsActive) ?>">
                                <i class="nav-icon fas fa-undo"></i>
                                <p>Devoluciones</p>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($has('manage_payment_methods')): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/payment-methods" class="nav-link<?= $link($paymentMethodsActive) ?>">
                                <i class="nav-icon fas fa-credit-card"></i>
                                <p>Métodos de pago</p>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
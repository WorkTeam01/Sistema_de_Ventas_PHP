<?php

use App\Controllers\ActivityLogController;
use App\Controllers\InventoryController;
use App\Controllers\ReportController;
use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\ProductController;
use App\Controllers\PurchaseController;
use App\Controllers\SaleController;
use App\Controllers\PermissionController;
use App\Controllers\RoleController;
use App\Controllers\SupplierController;
use App\Controllers\UserController;
use App\Core\Router;

/**
 * Rutas de la aplicación
 *
 * Módulos migrados a MVC: auth y users.
 * Las rutas aquí registradas se resuelven desde public/index.php.
 *
 * Ejemplo (descomentar cuando se migre un módulo):
 *
 * $router->get('/almacen',         'App\Controllers\AlmacenController@index');
 * $router->get('/almacen/create',  'App\Controllers\AlmacenController@create');
 * $router->post('/almacen/store',  'App\Controllers\AlmacenController@store');
 */

$router = new Router();

// Dashboard
$router->get('/', [DashboardController::class, 'index'], ['auth', 'can:view_dashboard']);

// Rutas del módulo auth (login/logout/password reset)
$router->get('/auth',        [AuthController::class, 'showLogin'], ['guest']);
$router->post('/auth/login', [AuthController::class, 'store'], ['guest']);
$router->get('/auth/logout', [AuthController::class, 'logout'], ['auth']);
$router->get('/auth/forgot-password',          [AuthController::class, 'forgotPassword'], ['guest']);
$router->post('/auth/forgot-password',         [AuthController::class, 'sendResetLink'],  ['guest']);
$router->get('/auth/reset-password/{token}',   [AuthController::class, 'showResetForm'],  ['guest']);
$router->post('/auth/reset-password',          [AuthController::class, 'resetPassword'],  ['guest']);

// Perfil propio (cualquier rol autenticado)
$router->get('/profile',           [UserController::class, 'profile'],        ['auth']);
$router->post('/profile/update',   [UserController::class, 'updateProfile'],  ['auth']);
$router->post('/profile/password', [UserController::class, 'updatePassword'], ['auth']);

// Rutas del módulo users (MVC)
$router->get('/users',              [UserController::class, 'index'],       ['auth', 'can:manage_users']);
$router->get('/users/create',       [UserController::class, 'create'],      ['auth', 'can:manage_users']);
$router->post('/users',             [UserController::class, 'store'],       ['auth', 'can:manage_users']);
$router->get('/users/edit/{id}',    [UserController::class, 'edit'],        ['auth', 'can:manage_users']);
$router->post('/users/update',      [UserController::class, 'update'],      ['auth', 'can:manage_users']);
$router->post('/users/check-email', [UserController::class, 'checkEmail'],  ['auth', 'can:manage_users']);
$router->get('/users/check/{id}',   [UserController::class, 'check'],       ['auth', 'can:manage_users']);
$router->get('/users/delete/{id}',  [UserController::class, 'delete'],      ['auth', 'can:manage_users']);
$router->post('/users/delete',      [UserController::class, 'destroy'],     ['auth', 'can:manage_users']);

// Rutas del módulo roles (MVC)
$router->get('/roles',               [RoleController::class, 'index'],       ['auth', 'can:manage_roles']);
$router->post('/roles/store',        [RoleController::class, 'store'],       ['auth', 'can:manage_roles']);
$router->get('/roles/show/{id}',     [RoleController::class, 'show'],        ['auth', 'can:manage_roles']);
$router->post('/roles/update/{id}',  [RoleController::class, 'update'],      ['auth', 'can:manage_roles']);
$router->post('/roles/check-nombre', [RoleController::class, 'checkNombre'], ['auth', 'can:manage_roles']);

// Rutas del módulo permissions (MVC)
$router->get('/permissions',              [PermissionController::class, 'index'],      ['auth', 'can:manage_roles']);
$router->post('/permissions/store',       [PermissionController::class, 'store'],      ['auth', 'can:manage_roles']);
$router->get('/permissions/show/{id}',    [PermissionController::class, 'show'],       ['auth', 'can:manage_roles']);
$router->post('/permissions/update/{id}', [PermissionController::class, 'update'],     ['auth', 'can:manage_roles']);
$router->post('/permissions/check-clave', [PermissionController::class, 'checkClave'], ['auth', 'can:manage_roles']);

// Rutas del módulo categories (MVC)
$router->get('/categories',               [CategoryController::class, 'index'],       ['auth', 'can:view_categories']);
$router->post('/categories/store',        [CategoryController::class, 'store'],       ['auth', 'can:manage_categories']);
$router->get('/categories/show/{id}',     [CategoryController::class, 'show'],        ['auth', 'can:view_categories']);
$router->post('/categories/update/{id}',  [CategoryController::class, 'update'],      ['auth', 'can:manage_categories']);
$router->post('/categories/check-nombre', [CategoryController::class, 'checkNombre'], ['auth', 'can:manage_categories']);

// Rutas del módulo suppliers (MVC)
$router->get('/suppliers',               [SupplierController::class, 'index'],       ['auth', 'can:view_suppliers']);
$router->post('/suppliers/store',        [SupplierController::class, 'store'],       ['auth', 'can:manage_suppliers']);
$router->get('/suppliers/show/{id}',     [SupplierController::class, 'show'],        ['auth', 'can:view_suppliers']);
$router->post('/suppliers/update/{id}',  [SupplierController::class, 'update'],      ['auth', 'can:manage_suppliers']);
$router->post('/suppliers/check-nombre', [SupplierController::class, 'checkNombre'], ['auth', 'can:manage_suppliers']);
$router->post('/suppliers/delete',       [SupplierController::class, 'destroy'],     ['auth', 'can:manage_suppliers']);

// Rutas del módulo clients (MVC)
$router->get('/clients',               [ClientController::class, 'index'],      ['auth', 'can:view_clients']);
$router->post('/clients/store',        [ClientController::class, 'store'],      ['auth', 'can:manage_clients']);
$router->post('/clients/check-nit-ci', [ClientController::class, 'checkNitCi'], ['auth', 'can:manage_clients']);
$router->post('/clients/check-email',  [ClientController::class, 'checkEmail'], ['auth', 'can:manage_clients']);
$router->get('/clients/show/{id}',     [ClientController::class, 'show'],       ['auth', 'can:view_clients']);
$router->post('/clients/update/{id}',  [ClientController::class, 'update'],     ['auth', 'can:manage_clients']);
$router->post('/clients/delete',       [ClientController::class, 'destroy'],    ['auth', 'can:manage_clients']);

// Rutas del módulo products/almacen (MVC)
$router->get('/products',              [ProductController::class, 'index'],   ['auth', 'can:view_products']);
$router->get('/products/show/{id}',    [ProductController::class, 'show'],    ['auth', 'can:view_products']);
$router->get('/products/create',       [ProductController::class, 'create'],  ['auth', 'can:manage_products']);
$router->post('/products',             [ProductController::class, 'store'],   ['auth', 'can:manage_products']);
$router->get('/products/edit/{id}',    [ProductController::class, 'edit'],    ['auth', 'can:manage_products']);
$router->post('/products/update',      [ProductController::class, 'update'],  ['auth', 'can:manage_products']);
$router->get('/products/check/{id}',   [ProductController::class, 'check'],   ['auth', 'can:view_products']);
$router->get('/products/delete/{id}',  [ProductController::class, 'delete'],  ['auth', 'can:manage_products']);
$router->post('/products/delete',      [ProductController::class, 'destroy'], ['auth', 'can:manage_products']);

// Rutas del módulo purchases/compras (MVC)
$router->get('/purchases',             [PurchaseController::class, 'index'],   ['auth', 'can:view_purchases']);
$router->get('/purchases/create',      [PurchaseController::class, 'create'],  ['auth', 'can:manage_purchases']);
$router->post('/purchases',            [PurchaseController::class, 'store'],   ['auth', 'can:manage_purchases']);
$router->get('/purchases/show/{id}',   [PurchaseController::class, 'show'],    ['auth', 'can:view_purchases']);
$router->get('/purchases/report/{id}', [PurchaseController::class, 'report'],  ['auth', 'can:view_purchases']);
$router->get('/purchases/edit/{id}',   [PurchaseController::class, 'edit'],    ['auth', 'can:manage_purchases']);
$router->post('/purchases/update',     [PurchaseController::class, 'update'],  ['auth', 'can:manage_purchases']);
$router->post('/purchases/delete',     [PurchaseController::class, 'destroy'], ['auth', 'can:manage_purchases']);

// Rutas del módulo sales/ventas (MVC) — literales antes de paramétricas
$router->get('/sales',              [SaleController::class, 'index'],          ['auth', 'can:view_sales']);
$router->get('/sales/create',       [SaleController::class, 'create'],         ['auth', 'can:manage_sales']);
$router->post('/sales/cart/add',    [SaleController::class, 'addToCart'],      ['auth', 'can:manage_sales']);
$router->post('/sales/cart/remove', [SaleController::class, 'removeFromCart'], ['auth', 'can:manage_sales']);
$router->post('/sales/cancel',      [SaleController::class, 'cancel'],         ['auth', 'can:manage_sales']);
$router->post('/sales',             [SaleController::class, 'store'],          ['auth', 'can:manage_sales']);
$router->get('/sales/show/{id}',    [SaleController::class, 'show'],           ['auth', 'can:view_sales']);
$router->get('/sales/delete/{id}',  [SaleController::class, 'confirmDelete'],  ['auth', 'can:manage_sales']);
$router->get('/sales/invoice/{id}', [SaleController::class, 'invoice'],        ['auth', 'can:view_sales']);
$router->post('/sales/delete',      [SaleController::class, 'destroy'],        ['auth', 'can:manage_sales']);

// Rutas del módulo activity-log (solo Administrador)
$router->get('/activity-log',           [ActivityLogController::class, 'index'], ['auth', 'can:view_activity_log']);
$router->get('/activity-log/show/{id}', [ActivityLogController::class, 'show'],  ['auth', 'can:view_activity_log']);

// Rutas del módulo inventario (solo Administrador)
$router->get('/inventory',              [InventoryController::class, 'index'],           ['auth', 'can:manage_inventory']);
$router->post('/inventory/adjustments', [InventoryController::class, 'storeAdjustment'], ['auth', 'can:manage_inventory']);

// Rutas del módulo reportes
$router->get('/reports',              [ReportController::class, 'index'],       ['auth', 'can:view_reports']);
$router->get('/reports/sales',        [ReportController::class, 'sales'],       ['auth', 'can:view_sales_report']);
$router->get('/reports/purchases',    [ReportController::class, 'purchases'],   ['auth', 'can:view_purchases_report']);
$router->get('/reports/top-products', [ReportController::class, 'topProducts'], ['auth', 'can:view_top_products_report']);
$router->get('/reports/clients',      [ReportController::class, 'clients'],     ['auth', 'can:view_clients_report']);

// Error pages
$router->get('/errors/403', function () {
    require_once __DIR__ . '/../views/errors/403.php';
});

return $router;

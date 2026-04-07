<?php

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\ProductController;
use App\Controllers\PurchaseController;
use App\Controllers\SaleController;
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
$router->get('/', [DashboardController::class, 'index'], ['auth']);

// Rutas del módulo auth (login/logout)
$router->get('/auth',        [AuthController::class, 'showLogin'], ['guest']);
$router->post('/auth/login', [AuthController::class, 'store'], ['guest']);
$router->get('/auth/logout', [AuthController::class, 'logout'], ['auth']);

// Perfil propio (cualquier rol autenticado)
$router->get('/profile',           [UserController::class, 'profile'],        ['auth']);
$router->post('/profile/update',   [UserController::class, 'updateProfile'],  ['auth']);
$router->post('/profile/password', [UserController::class, 'updatePassword'], ['auth']);

// Rutas del módulo users (MVC)
$router->get('/users',         [UserController::class, 'index'], ['auth', 'admin']);
$router->get('/users/create',  [UserController::class, 'create'], ['auth', 'admin']);
$router->post('/users',        [UserController::class, 'store'], ['auth', 'admin']);
$router->get('/users/edit/{id}', [UserController::class, 'edit'], ['auth', 'admin']);
$router->post('/users/update', [UserController::class, 'update'], ['auth', 'admin']);
$router->post('/users/check-email', [UserController::class, 'checkEmail'], ['auth', 'admin']);
$router->get('/users/check/{id}', [UserController::class, 'check'], ['auth', 'admin']);
$router->get('/users/delete/{id}', [UserController::class, 'delete'], ['auth', 'admin']);
$router->post('/users/delete', [UserController::class, 'destroy'], ['auth', 'admin']);

// Rutas del módulo roles (MVC)
$router->get('/roles',                 [RoleController::class, 'index'],      ['auth', 'admin']);
$router->post('/roles/store',          [RoleController::class, 'store'],      ['auth', 'admin']);
$router->get('/roles/show/{id}',       [RoleController::class, 'show'],       ['auth', 'admin']);
$router->post('/roles/update/{id}',    [RoleController::class, 'update'],     ['auth', 'admin']);
$router->post('/roles/check-nombre',   [RoleController::class, 'checkNombre'],['auth', 'admin']);

// Rutas del módulo categories (MVC)
$router->get('/categories',                 [CategoryController::class, 'index'],       ['auth']);
$router->post('/categories/store',          [CategoryController::class, 'store'],       ['auth']);
$router->get('/categories/show/{id}',       [CategoryController::class, 'show'],        ['auth']);
$router->post('/categories/update/{id}',    [CategoryController::class, 'update'],      ['auth']);
$router->post('/categories/check-nombre',   [CategoryController::class, 'checkNombre'], ['auth']);

// Rutas del módulo suppliers (MVC)
$router->get('/suppliers',               [SupplierController::class, 'index'],       ['auth']);
$router->post('/suppliers/store',        [SupplierController::class, 'store'],       ['auth']);
$router->get('/suppliers/show/{id}',     [SupplierController::class, 'show'],        ['auth']);
$router->post('/suppliers/update/{id}',  [SupplierController::class, 'update'],      ['auth']);
$router->post('/suppliers/check-nombre', [SupplierController::class, 'checkNombre'], ['auth']);
$router->post('/suppliers/delete',       [SupplierController::class, 'destroy'],     ['auth']);

// Rutas del módulo clients (MVC)
$router->get('/clients',                  [ClientController::class, 'index'],      ['auth']);
$router->post('/clients/store',           [ClientController::class, 'store'],      ['auth']);
$router->post('/clients/check-nit-ci',    [ClientController::class, 'checkNitCi'], ['auth']);
$router->post('/clients/check-email',     [ClientController::class, 'checkEmail'], ['auth']);
$router->get('/clients/show/{id}',        [ClientController::class, 'show'],       ['auth']);
$router->post('/clients/update/{id}',     [ClientController::class, 'update'],     ['auth']);
$router->post('/clients/delete',          [ClientController::class, 'destroy'],    ['auth']);

// Rutas del módulo products/almacen (MVC)
$router->get('/products',              [ProductController::class, 'index'],   ['auth']);
$router->get('/products/show/{id}',    [ProductController::class, 'show'],    ['auth']);
$router->get('/products/create',       [ProductController::class, 'create'],  ['auth']);
$router->post('/products',            [ProductController::class, 'store'],   ['auth']);
$router->get('/products/edit/{id}',   [ProductController::class, 'edit'],    ['auth']);
$router->post('/products/update',     [ProductController::class, 'update'],  ['auth']);
$router->get('/products/check/{id}',  [ProductController::class, 'check'],   ['auth']);
$router->get('/products/delete/{id}', [ProductController::class, 'delete'],  ['auth']);
$router->post('/products/delete',     [ProductController::class, 'destroy'], ['auth']);

// Rutas del módulo purchases/compras (MVC)
$router->get('/purchases',             [PurchaseController::class, 'index'],   ['auth']);
$router->get('/purchases/create',      [PurchaseController::class, 'create'],  ['auth']);
$router->post('/purchases',            [PurchaseController::class, 'store'],   ['auth']);
$router->get('/purchases/show/{id}',   [PurchaseController::class, 'show'],    ['auth']);
$router->get('/purchases/edit/{id}',   [PurchaseController::class, 'edit'],    ['auth']);
$router->post('/purchases/update',     [PurchaseController::class, 'update'],  ['auth']);
$router->post('/purchases/delete',     [PurchaseController::class, 'destroy'], ['auth']);

// Rutas del módulo sales/ventas (MVC) — literales antes de paramétricas
$router->get('/sales',                [SaleController::class, 'index'],         ['auth', 'seller']);
$router->get('/sales/create',         [SaleController::class, 'create'],        ['auth', 'seller']);
$router->post('/sales/cart/add',      [SaleController::class, 'addToCart'],     ['auth', 'seller']);
$router->post('/sales/cart/remove',   [SaleController::class, 'removeFromCart'],['auth', 'seller']);
$router->post('/sales',               [SaleController::class, 'store'],         ['auth', 'seller']);
$router->get('/sales/show/{id}',      [SaleController::class, 'show'],          ['auth', 'seller']);
$router->get('/sales/delete/{id}',    [SaleController::class, 'confirmDelete'], ['auth', 'seller']);
$router->get('/sales/invoice/{id}',   [SaleController::class, 'invoice'],       ['auth', 'seller']);
$router->post('/sales/delete',        [SaleController::class, 'destroy'],       ['auth', 'seller']);

// Error pages
$router->get('/errors/403', function () {
    require_once __DIR__ . '/../views/errors/403.php';
});

return $router;

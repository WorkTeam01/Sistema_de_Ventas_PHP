<?php

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\ProductController;
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

// Rutas del módulo users (MVC)
$router->get('/users',         [UserController::class, 'index'], ['auth', 'admin']);
$router->get('/users/create',  [UserController::class, 'create'], ['auth', 'admin']);
$router->post('/users',        [UserController::class, 'store'], ['auth', 'admin']);
$router->get('/users/show/{id}', [UserController::class, 'show'], ['auth', 'admin']);
$router->get('/users/edit/{id}', [UserController::class, 'edit'], ['auth', 'admin']);
$router->post('/users/update', [UserController::class, 'update'], ['auth', 'admin']);
$router->get('/users/delete/{id}', [UserController::class, 'delete'], ['auth', 'admin']);
$router->post('/users/delete', [UserController::class, 'destroy'], ['auth', 'admin']);

// Rutas del módulo roles (MVC)
$router->get('/roles',           [RoleController::class, 'index'],  ['auth', 'admin']);
$router->get('/roles/create',    [RoleController::class, 'create'], ['auth', 'admin']);
$router->post('/roles',          [RoleController::class, 'store'],  ['auth', 'admin']);
$router->get('/roles/edit/{id}', [RoleController::class, 'edit'],   ['auth', 'admin']);
$router->post('/roles/update',   [RoleController::class, 'update'], ['auth', 'admin']);

// Rutas del módulo categories (MVC)
$router->get('/categories',           [CategoryController::class, 'index'],  ['auth']);
$router->get('/categories/create',    [CategoryController::class, 'create'], ['auth']);
$router->post('/categories',          [CategoryController::class, 'store'],  ['auth']);
$router->get('/categories/edit/{id}', [CategoryController::class, 'edit'],   ['auth']);
$router->post('/categories/update',   [CategoryController::class, 'update'], ['auth']);

// Rutas del módulo suppliers (MVC)
$router->get('/suppliers',              [SupplierController::class, 'index'],   ['auth']);
$router->get('/suppliers/create',       [SupplierController::class, 'create'],  ['auth']);
$router->post('/suppliers',             [SupplierController::class, 'store'],   ['auth']);
$router->get('/suppliers/edit/{id}',    [SupplierController::class, 'edit'],    ['auth']);
$router->post('/suppliers/update',      [SupplierController::class, 'update'],  ['auth']);
$router->post('/suppliers/delete',      [SupplierController::class, 'destroy'], ['auth']);

// Rutas del módulo clients (MVC)
$router->get('/clients',             [ClientController::class, 'index'],   ['auth']);
$router->get('/clients/create',      [ClientController::class, 'create'],  ['auth']);
$router->post('/clients',            [ClientController::class, 'store'],   ['auth']);
$router->get('/clients/edit/{id}',   [ClientController::class, 'edit'],    ['auth']);
$router->post('/clients/update',     [ClientController::class, 'update'],  ['auth']);
$router->post('/clients/delete',     [ClientController::class, 'destroy'], ['auth']);

// Rutas del módulo products/almacen (MVC)
$router->get('/products',              [ProductController::class, 'index'],   ['auth']);
$router->get('/products/show/{id}',    [ProductController::class, 'show'],    ['auth']);
$router->get('/products/create',       [ProductController::class, 'create'],  ['auth']);
$router->post('/products',            [ProductController::class, 'store'],   ['auth']);
$router->get('/products/edit/{id}',   [ProductController::class, 'edit'],    ['auth']);
$router->post('/products/update',     [ProductController::class, 'update'],  ['auth']);
$router->post('/products/delete',     [ProductController::class, 'destroy'], ['auth']);

return $router;

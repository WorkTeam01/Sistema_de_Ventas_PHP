<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
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

return $router;

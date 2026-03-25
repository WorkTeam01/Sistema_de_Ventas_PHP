<?php

use App\Core\Router;

/**
 * Rutas de la aplicación
 *
 * Módulo auth — las páginas de login/logout se sirven como archivos reales
 * (auth/index.php, auth/login.php, auth/logout.php) para compatibilidad XAMPP.
 * Las rutas aquí registradas son para futuros módulos que migren a MVC completo.
 *
 * Ejemplo (descomentar cuando se migre un módulo):
 *
 * $router->get('/almacen',         'App\Controllers\AlmacenController@index');
 * $router->get('/almacen/create',  'App\Controllers\AlmacenController@create');
 * $router->post('/almacen/store',  'App\Controllers\AlmacenController@store');
 */

$router = new Router();

// Rutas del módulo auth (login/logout)
$router->get('/auth',         'App\Controllers\AuthController@showLogin');
$router->post('/auth/login',  'App\Controllers\AuthController@store');
$router->get('/auth/logout',  'App\Controllers\AuthController@logout');

return $router;

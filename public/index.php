<?php

require_once __DIR__ . '/../app/config.php';

use App\Core\Router;

$router = new Router();

// Rutas de autenticación
$router->get('/auth',        'App\Controllers\AuthController@showLogin');
$router->post('/auth/login', 'App\Controllers\AuthController@store');
$router->get('/auth/logout', 'App\Controllers\AuthController@logout');

// Extraer URI relativa (sin el basePath del proyecto y /public)
$basePath = rtrim(parse_url($_ENV['APP_URL'], PHP_URL_PATH), '/');
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . ltrim(substr($uri, strlen($basePath) + strlen('/public')), '/');

$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);

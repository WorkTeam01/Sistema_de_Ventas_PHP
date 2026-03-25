<?php

require_once __DIR__ . '/../app/config.php';

/** @var App\Core\Router $router */
$router = require_once __DIR__ . '/../routes/web.php';

// Extraer URI relativa (sin el basePath del proyecto)
$basePath = rtrim(parse_url($_ENV['APP_URL'], PHP_URL_PATH), '/');
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . ltrim(substr($uri, strlen($basePath)), '/');

$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);

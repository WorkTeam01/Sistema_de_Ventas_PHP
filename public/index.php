<?php

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

use App\Core\Router;

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Constantes globales
define('BASE_URL', rtrim($_ENV['APP_URL'], '/'));
define('APP_VERSION', $_ENV['APP_VERSION'] ?? '1.0.0');

// Zona horaria
date_default_timezone_set($_ENV['APP_TIMEZONE']);

// Cabeceras de seguridad (defensa en profundidad)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Variables backward-compat para vistas y controladores
$pdo = App\Core\Database::getInstance()->getConnection();
$URL = BASE_URL;
$Año = date('Y');
$fechaHora = date("Y-m-d H:i:s");

/** @var Router $router */
$router = require_once BASE_PATH . '/routes/web.php';

// Extraer URI relativa (sin el basePath del proyecto)
$basePath = rtrim(parse_url($_ENV['APP_URL'], PHP_URL_PATH), '/');
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = '/' . ltrim(substr($uri, strlen($basePath)), '/');

$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);

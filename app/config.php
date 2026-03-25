<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Backward compat: $pdo para todos los controladores existentes
$pdo = App\Core\Database::getInstance();

$URL = rtrim($_ENV['APP_URL'], '/');
$Año = date('Y');
date_default_timezone_set($_ENV['APP_TIMEZONE']);
$fechaHora = date("Y-m-d H:i:s");

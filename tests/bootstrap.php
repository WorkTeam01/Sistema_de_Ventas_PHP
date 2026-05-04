<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

// Inyectar SQLite in-memory antes de que cualquier modelo intente conectar a MySQL.
// Esto permite instanciar modelos en tests sin necesitar una BD real.
$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

Database::set($pdo);

<?php

define('SERVIDOR', 'localhost');
define('USUARIO', 'root');
define('PASSWORD', '');          // Contraseña de MySQL
define('BD', 'sistemadeventas');

$servidor = "mysql:dbname=" . BD . ";host=" . SERVIDOR;

try {
    $pdo = new PDO($servidor, USUARIO, PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit();
}

$URL = 'http://localhost/Sistema_de_Ventas_PHP'; // Ajustar al nombre real del directorio
$Año = date('Y');

date_default_timezone_set("America/La_Paz");

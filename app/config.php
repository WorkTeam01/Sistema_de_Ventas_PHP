<?php

define('SERVIDOR', 'localhost');
define('USUARIO', 'root');
define('PASSWORD', '');
define('BD', 'sistemadeventas');

$servidor = "mysql:dbname=" . BD . ";host=" . SERVIDOR;

try {
    $pdo = new PDO($servidor, USUARIO, PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
} catch (PDOException $e) {
    echo "Error al conectarse a la base de datos: " . $e->getMessage();
    exit();
}

$URL = 'http://localhost/sistemaventas';
$Año = date('Y');

date_default_timezone_set("America/La_Paz");
$fechaHora = date("Y-m-d H:i:s");

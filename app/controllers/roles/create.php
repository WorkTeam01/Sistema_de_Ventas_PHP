<?php

include_once '../../config.php';

$rol = $_POST['rol'];


$sentencia = $pdo->prepare("INSERT INTO tb_roles
    (rol, fyh_creacion) VALUES (:rol, :fyh_creacion)");

$sentencia->bindParam('rol', $rol);
$sentencia->bindParam('fyh_creacion', $fechaHora);

if ($sentencia->execute()) {
    session_start();
    $_SESSION['mensaje'] = 'EL rol se registró exitosamente';
    $_SESSION['icono'] = 'success';
    header('Location: ' . $URL . '/roles');
} else {
    session_start();
    $_SESSION['mensaje'] = 'Error al crear el rol';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/roles/create.php');
}

<?php

include_once '../../config.php';
session_start();
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Error de seguridad: Token CSRF inválido.");
}

$id_producto = $_POST['id_producto'];

$sentencia = $pdo->prepare("DELETE FROM tb_almacen WHERE id_producto = :id_producto");

$sentencia->bindParam('id_producto', $id_producto);

if ($sentencia->execute()) {
    session_start();
    $_SESSION['mensaje'] = "Se eliminó el producto exitosamente";
    $_SESSION['icono'] = "success";
    header('Location: ' . $URL . '/almacen');
} else {
    session_start();
    $_SESSION['mensaje'] = "Error al intentar eliminar el producto";
    $_SESSION['icono'] = "error";
    header('Location: ' . $URL . '/almacen/delete.php?id=' . $id_producto);
}

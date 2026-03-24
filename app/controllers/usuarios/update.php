<?php

include_once '../../config.php';
session_start();
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Error de seguridad: Token CSRF inválido.");
}

$nombres = $_POST['nombres'];
$email = $_POST['email'];
$rol = $_POST['rol'];
$password_user = $_POST['password_user'];
$password_repeat = $_POST['password_repeat'];
$id_usuario = $_POST['id_usuario'];

if ($password_user == "") {
    $sentencia = $pdo->prepare("UPDATE tb_usuarios
        SET nombres = :nombres,
        email = :email,
        id_rol = :id_rol,
        fyh_actualizacion = :fyh_actualizacion
        WHERE id_usuario = :id_usuario");

    $sentencia->bindParam('nombres', $nombres);
    $sentencia->bindParam('email', $email);
    $sentencia->bindParam('id_rol', $rol);
    $sentencia->bindParam('fyh_actualizacion', $fechaHora);
    $sentencia->bindParam('id_usuario', $id_usuario);
    $sentencia->execute();

    session_start();
    $_SESSION['mensaje'] = 'EL usuario se actualizó exitosamente';
    $_SESSION['icono'] = 'success';
    header('Location: ' . $URL . '/usuarios/');
} else {
    if ($password_user == $password_repeat) {
        $password_user = password_hash($password_user, PASSWORD_DEFAULT);
        $sentencia = $pdo->prepare("UPDATE tb_usuarios
        SET nombres = :nombres,
        email = :email,
        id_rol = :id_rol,
        password_user = :password_user,
        fyh_actualizacion = :fyh_actualizacion
        WHERE id_usuario = :id_usuario");

        $sentencia->bindParam('nombres', $nombres);
        $sentencia->bindParam('email', $email);
        $sentencia->bindParam('id_rol', $rol);
        $sentencia->bindParam('password_user', $password_user);
        $sentencia->bindParam('fyh_actualizacion', $fechaHora);
        $sentencia->bindParam('id_usuario', $id_usuario);
        $sentencia->execute();

        session_start();
        $_SESSION['mensaje'] = 'EL usuario se actualizó exitosamente';
        $_SESSION['icono'] = 'success';
        header('Location: ' . $URL . '/usuarios/');
    } else {
        //echo "Las contraseñas no coinciden";
        session_start();
        $_SESSION['mensaje'] = 'Las contraseñas no coinciden';
        $_SESSION['icono'] = 'error';
        header('Location: ' . $URL . '/usuarios/update.php?id=' . $id_usuario);
    }
}

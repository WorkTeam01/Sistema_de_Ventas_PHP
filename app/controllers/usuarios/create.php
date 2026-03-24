<?php

include_once '../../config.php';
session_start();
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("Error de seguridad: Token CSRF inválido.");
}

$nombres = trim($_POST['nombres']);
$email = trim($_POST['email']);
$rol = $_POST['rol'];
$password_user = $_POST['password_user'];
$password_repeat = $_POST['password_repeat'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    session_start();
    $_SESSION['mensaje'] = 'El formato del correo electrónico es inválido.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/usuarios/create.php');
    exit();
}

if ($password_user == $password_repeat) {
    $password_user = password_hash($password_user, PASSWORD_DEFAULT);
    $sentencia = $pdo->prepare("INSERT INTO tb_usuarios
    (nombres, email, id_rol, password_user, fyh_creacion)
    VALUES (:nombres, :email, :id_rol, :password_user, :fyh_creacion)");

    $sentencia->bindParam('nombres', $nombres);
    $sentencia->bindParam('email', $email);
    $sentencia->bindParam('id_rol', $rol);
    $sentencia->bindParam('password_user', $password_user);
    $sentencia->bindParam('fyh_creacion', $fechaHora);
    $sentencia->execute();

    session_start();
    $_SESSION['mensaje'] = 'EL usuario se registró exitosamente';
    $_SESSION['icono'] = 'success';
    header('Location: ' . $URL . '/usuarios/');
} else {
    //echo "Las contraseñas no coinciden";
    session_start();
    $_SESSION['mensaje'] = 'Las contraseñas no coinciden';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL . '/usuarios/create.php');
}

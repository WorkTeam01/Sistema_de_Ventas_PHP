<?php

$id_usuario_get = $_GET['id'];

$sql_usuarios = "SELECT us.id_usuario, us.nombres, us.email, rol.rol FROM tb_usuarios us
                INNER JOIN tb_roles rol on us.id_rol = rol.id_rol
                WHERE us.id_usuario = '$id_usuario_get'";
$query_usuarios = $pdo->query($sql_usuarios);
$query_usuarios->execute();

$usuarios_datos = $query_usuarios->fetchAll(PDO::FETCH_ASSOC);

foreach ($usuarios_datos as $usuarios_dato) {
    $nombres = $usuarios_dato['nombres'];
    $email = $usuarios_dato['email'];
    $rol = $usuarios_dato['rol'];
}

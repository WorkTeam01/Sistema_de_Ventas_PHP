<?php
if (session_status() == PHP_SESSION_NONE)
    session_start();

if (isset($_SESSION['sesion_email'])) {
    $email_sesion = $_SESSION['sesion_email'];
    $sql = "SELECT us.id_usuario, us.nombres, us.email, rol.rol FROM tb_usuarios us
                INNER JOIN tb_roles rol on us.id_rol = rol.id_rol WHERE email = '$email_sesion'";
    $query = $pdo->prepare($sql);
    $query->execute();

    $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $usuario) {
        $id_usuario_sesion = $usuario['id_usuario'];
        $nombres_sesion = $usuario['nombres'];
        $rol_sesion = $usuario['rol'];
    }
} else {
    echo "No existe sesion";
    header('Location:' . $URL . '/login');
}

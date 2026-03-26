<?php
if (session_status() == PHP_SESSION_NONE)
    session_start();

if (isset($_SESSION['sesion_email'])) {
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $email_sesion = $_SESSION['sesion_email'];
    $sql = "SELECT us.id_usuario, us.nombres, us.email, rol.rol FROM tb_usuarios us
                INNER JOIN tb_roles rol on us.id_rol = rol.id_rol WHERE email = ?";
    $query = $pdo->prepare($sql);
    $query->execute([$email_sesion]);

    $usuarios = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $usuario) {
        $id_usuario_sesion = $usuario['id_usuario'];
        $nombres_sesion = $usuario['nombres'];
        $rol_sesion = $usuario['rol'];
    }
} else {
    header('Location:' . $URL . '/auth');
    exit();
}

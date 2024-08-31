<?php

class AuthMiddleware
{
    private $pdo;
    private $URL;

    public function __construct($pdo, $URL)
    {
        $this->pdo = $pdo;
        $this->URL = $URL;
    }

    public function verificarSesion()
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (!isset($_SESSION['sesion_email'])) {
            $_SESSION['mensaje'] = 'Debes iniciar sesión para acceder a esta página.';
            $_SESSION['icono'] = 'warning';
            header('Location: ' . $this->URL . '/login');
            exit();
        }
    }

    public function obtenerDatosUsuario($email)
    {
        $sql = "SELECT us.id_usuario, us.nombres, us.email, rol.id_rol, rol.rol 
                FROM tb_usuarios us
                INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol 
                WHERE email = :email";
        $query = $this->pdo->prepare($sql);
        $query->execute(['email' => $email]);
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function verificarPermiso($rol_requerido)
    {
        $this->verificarSesion();
        $usuario = $this->obtenerDatosUsuario($_SESSION['sesion_email']);
        if ($usuario['rol'] != $rol_requerido) {
            $_SESSION['mensaje'] = 'No tienes permisos para acceder a esta página.';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $this->URL . '/error/error.php');
            exit();
        }
        return $usuario;
    }

    public function verificarRoles($roles_permitidos)
    {
        $this->verificarSesion();
        $usuario = $this->obtenerDatosUsuario($_SESSION['sesion_email']);
        if (!in_array($usuario['rol'], $roles_permitidos)) {
            $_SESSION['mensaje'] = 'No tienes permisos para acceder a esta página.';
            $_SESSION['icono'] = 'error';
            header('Location: ' . $this->URL . '/error/error.php');
            exit();
        }
        return $usuario;
    }
}
/*
// Ejemplo de uso en una página protegida (ventas.php)
require_once 'config.php';
require_once 'middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarPermiso('Vendedor');

// Aquí va el código de la página de ventas
echo "Bienvenido a la página de ventas, " . $usuario['nombres'];

// Ejemplo de uso con múltiples roles permitidos
// $usuario = $auth->verificarRoles(['Administrador', 'Gerente']);
*/
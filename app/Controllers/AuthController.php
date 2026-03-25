<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Redirigir al dashboard si ya hay sesión activa
        if (isset($_SESSION['sesion_email'])) {
            $this->redirect(rtrim($_ENV['APP_URL'], '/') . '/index.php');
        }

        $respuesta = null;
        if (isset($_SESSION['mensaje'])) {
            $respuesta = $_SESSION['mensaje'];
            unset($_SESSION['mensaje']);
        }

        $this->view('auth/index.php', ['respuesta' => $respuesta]);
    }

    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $URL = rtrim($_ENV['APP_URL'], '/');

        // Validación CSRF
        if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            die("Error de seguridad: Token CSRF inválido.");
        }

        $email        = $_POST['email'] ?? '';
        $password_user = $_POST['password_user'] ?? '';

        $pdo   = Database::getInstance();
        $stmt  = $pdo->prepare("SELECT * FROM tb_usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password_user, $usuario['password_user'])) {
            $_SESSION['sesion_email'] = $email;
            $this->redirect($URL . '/index.php');
        } else {
            $_SESSION['mensaje'] = "Datos incorrectos";
            $this->redirect($URL . '/auth');
        }
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
        $this->redirect(rtrim($_ENV['APP_URL'], '/') . '/auth');
    }
}

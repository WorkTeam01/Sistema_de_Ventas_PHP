<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $URL = rtrim($_ENV['APP_URL'], '/');

        if (isset($_SESSION['sesion_email'])) {
            $this->redirect($URL . '/index.php');
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $respuesta = null;
        if (isset($_SESSION['mensaje'])) {
            $respuesta = $_SESSION['mensaje'];
            unset($_SESSION['mensaje']);
        }

        $this->view('views/auth/login.php', compact('URL', 'respuesta'));
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

        $email        = trim($_POST['email'] ?? '');
        $password_user = $_POST['password_user'] ?? '';

        $userModel = new User();
        $usuario = $userModel->verifyCredentials($email, $password_user);

        if ($usuario) {
            $_SESSION['sesion_email'] = $usuario['email'];
            $this->redirect($URL);
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

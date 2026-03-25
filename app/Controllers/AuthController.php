<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        Auth::startSession();

        $URL = rtrim($_ENV['APP_URL'], '/');

        if (Auth::check()) {
            $this->redirect($URL . '/index.php');
        }

        Auth::generateCsrfToken();

        $respuesta = null;
        if (isset($_SESSION['mensaje'])) {
            $respuesta = $_SESSION['mensaje'];
            unset($_SESSION['mensaje']);
        }

        $this->view('views/auth/login.php', compact('URL', 'respuesta'));
    }

    public function store(): void
    {
        Auth::startSession();

        $URL = rtrim($_ENV['APP_URL'], '/');

        // Validación CSRF
        if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            die("Error de seguridad: Token CSRF inválido.");
        }

        $email        = trim($_POST['email'] ?? '');
        $password_user = $_POST['password_user'] ?? '';

        $userModel = new User();
        $usuario = $userModel->verifyCredentials($email, $password_user);

        if ($usuario) {
            Auth::login($usuario);
            $this->redirect($URL);
        } else {
            $_SESSION['mensaje'] = "Datos incorrectos";
            $this->redirect($URL . '/auth');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(rtrim($_ENV['APP_URL'], '/') . '/auth');
    }
}

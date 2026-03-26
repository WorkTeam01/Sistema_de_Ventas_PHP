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

        if (Auth::check()) {
            $this->redirect(BASE_URL . '/');
        }

        Auth::generateCsrfToken();

        $respuesta = null;
        if (isset($_SESSION['mensaje'])) {
            $respuesta = $_SESSION['mensaje'];
            unset($_SESSION['mensaje']);
        }

        $this->view('views/auth/login.php', ['URL' => BASE_URL, 'respuesta' => $respuesta]);
    }

    public function store(): void
    {
        Auth::startSession();

        if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            die("Error de seguridad: Token CSRF inválido.");
        }

        $email         = trim($_POST['email'] ?? '');
        $password_user = $_POST['password_user'] ?? '';

        $userModel = new User();
        $usuario   = $userModel->verifyCredentials($email, $password_user);

        if ($usuario) {
            Auth::login($usuario);
            $this->redirect(BASE_URL . '/');
        } else {
            $_SESSION['mensaje'] = "Datos incorrectos";
            $this->redirect(BASE_URL . '/auth');
        }
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(BASE_URL . '/auth');
    }
}

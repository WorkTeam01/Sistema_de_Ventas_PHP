<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Muestra la vista de inicio de sesión.
     * Si el usuario ya tiene sesión activa lo redirige al dashboard.
     */
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

    /**
     * Procesa el formulario de inicio de sesión.
     * Valida CSRF, verifica credenciales e inicia la sesión.
     */
    public function store(): void
    {
        Auth::startSession();

        $this->validateCsrfOrFail();

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

    /**
     * Cierra la sesión activa y redirige al login.
     */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect(BASE_URL . '/auth');
    }
}

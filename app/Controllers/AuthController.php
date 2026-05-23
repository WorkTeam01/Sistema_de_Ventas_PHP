<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;
use App\Services\EmailService;

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

        $this->view('views/auth/login.php', ['URL' => BASE_URL], withMessages: true);
    }

    /**
     * Procesa el formulario de inicio de sesión.
     * Valida CSRF, verifica credenciales e inicia la sesión.
     */
    public function store(): void
    {
        Auth::startSession();

        $this->validateCsrfOrFail();

        $email = trim($_POST['email'] ?? '');
        $password_user = $_POST['password_user'] ?? '';
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

        $userModel = new User();
        $usuario = $userModel->findByEmail($email);

        if ($usuario && $userModel->isLocked($usuario)) {
            $_SESSION['mensaje'] = 'Demasiados intentos fallidos. Tu cuenta está bloqueada por 15 minutos. Si olvidaste tu contraseña, usa la opción ¿Olvidaste tu contraseña?';
            $_SESSION['icono'] = 'warning';
            $this->redirect(BASE_URL . '/auth');
            return;
        }

        if ($usuario && isset($usuario['password_user']) && password_verify($password_user, $usuario['password_user'])) {
            $userModel->clearLoginAttempts((int)$usuario['id_usuario']);
            Auth::login($usuario, $remember);
            $_SESSION['welcome_user'] = $usuario['nombres'];
            $this->redirect(BASE_URL . '/');
        } else {
            if ($usuario) {
                $userModel->recordFailedLogin((int)$usuario['id_usuario']);
            }
            $_SESSION['mensaje'] = 'Datos incorrectos';
            $_SESSION['icono'] = 'error';
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

    /**
     * Muestra el formulario "¿Olvidaste tu contraseña?".
     */
    public function forgotPassword(): void
    {
        Auth::startSession();
        Auth::generateCsrfToken();

        $this->view('views/auth/forgot-password.php', ['URL' => BASE_URL], withMessages: true);
    }

    /**
     * Procesa el formulario de recuperación: genera el token y lo envía por email (o lo muestra en modo dev).
     */
    public function sendResetLink(): void
    {
        Auth::startSession();
        $this->validateCsrfOrFail();

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('Ingresa un correo electrónico válido.', 'error');
            $this->redirect(BASE_URL . '/auth/forgot-password');
        }

        $userModel = new User();
        $usuario = $userModel->findByEmail($email);
        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true' || ($_ENV['APP_ENV'] ?? '') === 'local';

        if (!$usuario) {
            if ($isDebug) {
                $this->flash('DEBUG: El correo ' . $email . ' no está registrado en el sistema.', 'error');
            } else {
                $this->flash('Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.', 'info');
            }
            $this->redirect(BASE_URL . '/auth/forgot-password');
        }

        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $userModel->storeResetToken((int)$usuario['id_usuario'], $token, $expiracion);

        $resetUrl = BASE_URL . '/auth/reset-password/' . $token;
        $emailService = new EmailService();
        $emailSent = $emailService->sendResetLink($email, $usuario['nombres'], $resetUrl);

        if ($isDebug) {
            $this->view('views/auth/show-reset-link.php', [
                'URL' => BASE_URL,
                'resetUrl' => $resetUrl,
                'emailSent' => $emailSent,
                'email' => $email,
            ]);
            return;
        }

        $this->flash('Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.', 'info');
        $this->redirect(BASE_URL . '/auth/forgot-password');
    }

    /**
     * Muestra el formulario de nueva contraseña validando el token.
     *
     * @param string $token Token de restablecimiento recibido por email.
     */
    public function showResetForm(string $token): void
    {
        Auth::startSession();

        $userModel = new User();
        $usuario = $userModel->findByResetToken($token);

        if (!$usuario) {
            $this->flash('El enlace es inválido o ha expirado.', 'error');
            $this->redirect(BASE_URL . '/auth');
        }

        Auth::generateCsrfToken();

        $this->view('views/auth/reset-password.php', ['URL' => BASE_URL, 'token' => $token], withMessages: true);
    }

    /**
     * Procesa el formulario de nueva contraseña: valida el token, hashea y guarda la nueva contraseña.
     */
    public function resetPassword(): void
    {
        Auth::startSession();
        $this->validateCsrfOrFail();

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password) || strlen($password) < 8) {
            $this->flash('La contraseña debe tener al menos 8 caracteres.', 'error');
            $this->redirect(BASE_URL . '/auth/reset-password/' . $token);
        }

        if ($password !== $passwordConfirm) {
            $this->flash('Las contraseñas no coinciden.', 'error');
            $this->redirect(BASE_URL . '/auth/reset-password/' . $token);
        }

        $userModel = new User();
        $usuario = $userModel->findByResetToken($token);

        if (!$usuario) {
            $this->flash('El enlace es inválido o ha expirado.', 'error');
            $this->redirect(BASE_URL . '/auth');
        }

        $userModel->updatePassword((int)$usuario['id_usuario'], $password);
        $userModel->clearResetToken((int)$usuario['id_usuario']);

        $this->flash('Contraseña actualizada correctamente. Ya puedes iniciar sesión.', 'success');
        $this->redirect(BASE_URL . '/auth');
    }
}

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\AuthAttempt;
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

        $email    = User::normalizeEmail($email);
        $usuario  = (new User())->findByEmail($email) ?: null;
        $attempts = new AuthAttempt();
        $ip       = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        $attemptId = $attempts->register('login', $ip, $email, $usuario ? (int)$usuario['id_usuario'] : null);
        $blocked   = $attempts->isBlocked('login', $attemptId, $ip, $email);

        // Bloqueado no verifica la contraseña; los demás fallos responden con el mismo mensaje.
        if (!$blocked && User::passwordMatches($usuario, $password_user)) {
            $attempts->clear('login', $email);
            Auth::login($usuario, $remember);
            $_SESSION['welcome_user'] = $usuario['nombres'];
            ActivityLog::record('login', 'auth', (int)$usuario['id_usuario'], "Inicio de sesión exitoso: {$email}");
            $this->redirect(BASE_URL . '/');
            return;
        }

        $motivo = $blocked ? 'Intento de inicio de sesión bloqueado por exceso de intentos' : 'Intento fallido de inicio de sesión';
        ActivityLog::record('login_failed', 'auth', $usuario ? (int)$usuario['id_usuario'] : null, "{$motivo}: {$email}");
        $_SESSION['mensaje'] = 'Correo o contraseña incorrectos, o demasiados intentos. Si olvidaste tu contraseña, usa la opción ¿Olvidaste tu contraseña?';
        $_SESSION['icono'] = 'error';
        $this->redirect(BASE_URL . '/auth');
    }

    /**
     * Cierra la sesión activa y redirige al login.
     */
    public function logout(): void
    {
        $this->validateCsrfOrFail();

        $user = Auth::user();
        if ($user) {
            ActivityLog::record('logout', 'auth', (int)$user['id_usuario'], "Cierre de sesión: {$user['email']}");
        }
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
     * Procesa el formulario de recuperación: genera el token y lo envía por email. Responde siempre igual,
     * exista o no el correo, y nunca muestra el enlace en pantalla.
     */
    public function sendResetLink(): void
    {
        Auth::startSession();
        $this->validateCsrfOrFail();

        $email = User::normalizeEmail($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('Ingresa un correo electrónico válido.', 'error');
            $this->redirect(BASE_URL . '/auth/forgot-password');
        }

        $userModel = new User();
        $usuario   = $userModel->findByEmail($email) ?: null;
        $attempts  = new AuthAttempt();
        $ip        = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        $attemptId = $attempts->register('reset', $ip, $email, $usuario ? (int)$usuario['id_usuario'] : null);

        $sendMail = null;

        if ($usuario && !$attempts->resetThrottled($attemptId, $ip, (int)$usuario['id_usuario'])) {
            $token = bin2hex(random_bytes(32));
            $userModel->storeResetToken((int)$usuario['id_usuario'], hash('sha256', $token));

            $resetUrl = BASE_URL . '/auth/reset-password/' . $token;
            $sendMail = static function () use ($email, $usuario, $resetUrl): void {
                (new EmailService())->sendResetLink($email, $usuario['nombres'], $resetUrl);

                if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                    error_log("[reset-password] enlace para {$email}: {$resetUrl}");
                }
            };
        }

        $this->flash('Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.', 'info');

        // El SMTP tarda segundos: enviarlo antes de responder haría que el tiempo delate si el correo existe.
        $sendMail !== null
            ? $this->redirectThenRun(BASE_URL . '/auth/forgot-password', $sendMail)
            : $this->redirect(BASE_URL . '/auth/forgot-password');
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

        if (empty($token) || !User::validatePassword($password)) {
            $this->flash('La contraseña debe tener al menos ' . User::MIN_PASSWORD_LENGTH . ' caracteres.', 'error');
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

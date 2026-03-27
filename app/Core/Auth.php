<?php

namespace App\Core;

/**
 * Gestiona la sesión de usuario y los tokens CSRF.
 *
 * Todos los métodos son estáticos; no requiere instanciación.
 */
class Auth
{
    /**
     * Inicia la sesión PHP si aún no está activa.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Indica si existe una sesión activa (email guardado en sesión).
     */
    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION['sesion_email']);
    }

    /**
     * Devuelve el email del usuario en sesión, o null si no hay sesión.
     */
    public static function email(): ?string
    {
        self::startSession();
        return $_SESSION['sesion_email'] ?? null;
    }

    /**
     * Devuelve los datos completos del usuario en sesión consultando la BD,
     * incluyendo su rol. Devuelve null si no hay sesión activa.
     */
    public static function user(): ?array
    {
        $email = self::email();
        if (!$email) {
            return null;
        }

        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            "SELECT us.id_usuario, us.nombres, us.email, us.id_rol, rol.rol
             FROM tb_usuarios us
             INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol
             WHERE us.email = ?
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Devuelve el nombre del rol del usuario en sesión, o null si no hay sesión.
     */
    public static function role(): ?string
    {
        $user = self::user();
        return $user['rol'] ?? null;
    }

    /**
     * Devuelve el nombre del usuario en sesión, o null si no hay sesión.
     */
    public static function name(): ?string
    {
        $user = self::user();
        return $user['nombres'] ?? null;
    }

    /**
     * Inicia sesión: regenera el ID de sesión y guarda el email del usuario.
     *
     * @param array $user Datos del usuario; debe contener la clave 'email'.
     */
    public static function login(array $user): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['sesion_email'] = $user['email'] ?? null;
    }

    /**
     * Cierra la sesión: limpia $_SESSION, elimina la cookie de sesión y destruye la sesión.
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];

        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Genera un token CSRF de 64 caracteres hexadecimales y lo almacena en sesión.
     * Si ya existe un token en la sesión, lo devuelve sin generar uno nuevo.
     *
     * @return string Token CSRF activo.
     */
    public static function generateCsrfToken(): string
    {
        self::startSession();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Valida el token CSRF recibido contra el almacenado en sesión usando comparación segura.
     *
     * @param string $token Token recibido desde el formulario.
     * @return bool True si el token es válido.
     */
    public static function validateCsrfToken(string $token): bool
    {
        self::startSession();

        return !empty($_SESSION['csrf_token'])
            && !empty($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

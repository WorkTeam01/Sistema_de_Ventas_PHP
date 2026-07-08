<?php

namespace App\Core;

use App\Models\User;

/**
 * Gestiona la sesión de usuario y los tokens CSRF.
 *
 * Todos los métodos son estáticos; no requiere instanciación.
 */
class Auth
{
    private static ?array $cachedUser = null;

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
     * Indica si existe una sesión activa y no ha expirado por inactividad.
     */
    public static function check(): bool
    {
        self::startSession();

        if (empty($_SESSION['sesion_email'])) {
            return false;
        }

        $lifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 30) * 60;

        if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $lifetime) {
            self::logout();
            return false;
        }

        $_SESSION['last_activity'] = time();

        if (isset($_SESSION['id_rol'], $_SESSION['permisos_version'])) {
            $currentVersion = self::fetchPermissionsVersion((int)$_SESSION['id_rol']);
            if ($currentVersion !== (int)$_SESSION['permisos_version']) {
                self::loadPermissions((int)$_SESSION['id_rol']);
            }
        }

        return true;
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
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

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

        self::$cachedUser = $user ?: null;
        return self::$cachedUser;
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
     * Si $remember es true, emite una cookie de remember_token (30 días).
     *
     */
    private static function loadPermissions(int $idRol): void
    {
        $pdo  = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            "SELECT p.clave
             FROM tb_rol_permiso rp
             INNER JOIN tb_permisos p ON p.id_permiso = rp.id_permiso
             WHERE rp.id_rol = ?"
        );
        $stmt->execute([$idRol]);
        $_SESSION['permisos'] = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        $_SESSION['id_rol'] = $idRol;
        $_SESSION['permisos_version'] = self::fetchPermissionsVersion($idRol);
    }

    /**
     * Devuelve el contador permisos_version del rol, o 0 si la columna aún
     * no existe (tolera despliegues donde el código llegó antes que la migración).
     */
    private static function fetchPermissionsVersion(int $idRol): int
    {
        try {
            $pdo  = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT permisos_version FROM tb_roles WHERE id_rol = ?");
            $stmt->execute([$idRol]);
            return (int)($stmt->fetchColumn() ?: 0);
        } catch (\PDOException $e) {
            return 0;
        }
    }

    public static function can(string $permiso): bool
    {
        self::startSession();
        return in_array($permiso, $_SESSION['permisos'] ?? [], true);
    }

    public static function isAdmin(): bool
    {
        return self::can('is_superadmin');
    }

    public static function refreshPermissions(): void
    {
        $user = self::user();
        if ($user) {
            self::loadPermissions((int) $user['id_rol']);
        }
    }

    public static function login(array $user, bool $remember = false): void
    {
        self::$cachedUser = null;
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['sesion_email']  = $user['email'] ?? null;
        $_SESSION['last_activity'] = time();

        $fullUser = self::user();
        if ($fullUser) {
            self::loadPermissions((int) $fullUser['id_rol']);
        }

        if ($remember && !empty($user['id_usuario'])) {
            $plain        = bin2hex(random_bytes(32));
            $hash         = hash('sha256', $plain);
            $days         = (int)($_ENV['REMEMBER_LIFETIME'] ?? 14);
            $seconds      = $days * 24 * 3600;
            $expiry       = date('Y-m-d H:i:s', time() + $seconds);

            $userModel = new User();
            $userModel->storeRememberToken((int)$user['id_usuario'], $hash, $expiry);

            setcookie('remember_token', $user['id_usuario'] . ':' . $plain, [
                'expires'  => time() + $seconds,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }
    }

    /**
     * Intenta autenticar al usuario a partir de la cookie remember_token.
     * Rota el token en cada auto-login para mitigar robo de cookie.
     */
    public static function loginWithCookie(): bool
    {
        if (empty($_COOKIE['remember_token'])) {
            return false;
        }

        $parts = explode(':', $_COOKIE['remember_token'], 2);

        if (count($parts) !== 2 || empty($parts[0]) || empty($parts[1])) {
            self::clearRememberCookie();
            return false;
        }

        [$id, $plain] = $parts;
        $hash = hash('sha256', $plain);

        $userModel = new User();
        $user = $userModel->findByRememberToken((int)$id, $hash);

        if (!$user) {
            $userModel->clearRememberToken((int)$id);
            self::clearRememberCookie();
            return false;
        }

        self::login($user, remember: true);
        return true;
    }

    /**
     * Cierra la sesión: limpia el remember_token en BD, borra cookie y destruye la sesión.
     */
    public static function logout(): void
    {
        self::startSession();

        if (!empty($_SESSION['sesion_email'])) {
            $user = self::user();
            if ($user) {
                $userModel = new User();
                $userModel->clearRememberToken((int)$user['id_usuario']);
            }
        }

        self::$cachedUser = null;
        self::clearRememberCookie();
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
     * Borra la cookie remember_token emitiendo una expiración en el pasado.
     */
    private static function clearRememberCookie(): void
    {
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', [
                'expires'  => time() - 42000,
                'path'     => '/',
                'secure'   => !empty($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }
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

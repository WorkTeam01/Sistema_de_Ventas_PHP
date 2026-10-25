<?php

namespace App\Core;

use App\Helpers\AuthSettings;
use App\Models\ActivityLog;
use App\Models\RememberToken;
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
            // El gc de PHP borra los datos de sesión a los 1440 s por defecto: sin esto la
            // inactividad real sería menor que SESSION_LIFETIME.
            ini_set('session.use_strict_mode', '1');
            ini_set('session.gc_maxlifetime', (string)max(1440, (int)($_ENV['SESSION_LIFETIME'] ?? 60) * 60));
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => self::isHttps(),
            ]);
            session_start();
        }
    }

    /**
     * Indica si existe una sesión activa, no ha expirado por inactividad y la
     * cuenta sigue siendo válida.
     *
     * Revalida contra la BD que la cuenta exista y que el rol de la sesión
     * coincida con el real: un usuario eliminado o degradado pierde los
     * permisos en la siguiente petición en vez de conservarlos hasta el
     * timeout de inactividad.
     */
    public static function check(): bool
    {
        self::startSession();

        if (empty($_SESSION['sesion_email'])) {
            return false;
        }

        $lifetime = (int)($_ENV['SESSION_LIFETIME'] ?? 60) * 60;

        // La inactividad solo cierra la sesión de PHP: no revoca el dispositivo recordado,
        // para que AuthMiddleware pueda reabrirla con la cookie "recordarme".
        if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $lifetime) {
            self::destroySession();
            return false;
        }

        $_SESSION['last_activity'] = time();

        $identity = self::fetchSessionIdentity((string)$_SESSION['sesion_email']);

        if ($identity === null) {
            self::logout();
            return false;
        }

        // Cambiar o restablecer la contraseña cierra las demás sesiones abiertas de la cuenta.
        // Una sesión anterior a este control no trae huella: se adopta la actual.
        if (!isset($_SESSION['pw_fp'])) {
            $_SESSION['pw_fp'] = $identity['pw_fp'];
        } elseif (!hash_equals((string)$_SESSION['pw_fp'], (string)$identity['pw_fp'])) {
            self::logout();
            return false;
        }

        // La comparación incluye el id_rol además de la versión: si el rol
        // cambió, la versión podría coincidir de forma accidental.
        if (isset($_SESSION['id_rol'], $_SESSION['permisos_version'])
            && ((int)$identity['id_rol'] !== (int)$_SESSION['id_rol']
                || (int)$identity['permisos_version'] !== (int)$_SESSION['permisos_version'])) {
            self::loadPermissions((int)$identity['id_rol']);
        }

        return true;
    }

    /**
     * Rol real y versión de permisos de la cuenta en sesión.
     *
     * @return array{id_rol:int, permisos_version:int, pw_fp:string}|null null si la cuenta ya
     *      no existe; en fallo de BD devuelve la identidad guardada en sesión
     *      para no invalidarla por un error transitorio.
     */
    private static function fetchSessionIdentity(string $email): ?array
    {
        try {
            $pdo  = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare(
                "SELECT u.id_rol, r.permisos_version, u.password_user
                 FROM tb_usuarios u
                 INNER JOIN tb_roles r ON r.id_rol = u.id_rol
                 WHERE u.email = ?
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $identity = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$identity) {
                return null;
            }
            $identity['pw_fp'] = self::passwordFingerprint((string)$identity['password_user']);
            unset($identity['password_user']);
            return $identity;
        } catch (\PDOException $e) {
            return [
                'id_rol'           => (int)($_SESSION['id_rol'] ?? 0),
                'permisos_version' => (int)($_SESSION['permisos_version'] ?? -1),
                'pw_fp'            => (string)($_SESSION['pw_fp'] ?? ''),
            ];
        }
    }

    private static function passwordFingerprint(string $passwordHash): string
    {
        return hash('sha256', $passwordHash);
    }

    /**
     * Vuelve a registrar la huella de contraseña de la sesión actual. Llamar tras cambiar la
     * contraseña propia, para que esa sesión no se cierre junto con las demás.
     */
    public static function syncPasswordFingerprint(): void
    {
        self::startSession();
        $email = (string)($_SESSION['sesion_email'] ?? '');
        if ($email === '') {
            return;
        }
        $identity = self::fetchSessionIdentity($email);
        if ($identity !== null) {
            $_SESSION['pw_fp'] = $identity['pw_fp'];
        }
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
        // El rol del usuario en caché quedó desactualizado al recargar.
        self::$cachedUser = null;
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

    /**
     * Inicia sesión: regenera el ID de sesión y el token CSRF, y carga los permisos.
     * Si $remember es true, emite una cookie "recordarme" para este dispositivo.
     */
    public static function login(array $user, bool $remember = false): void
    {
        self::$cachedUser = null;
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
        $_SESSION['sesion_email']  = $user['email'] ?? null;
        $_SESSION['last_activity'] = time();

        $fullUser = self::user();
        if ($fullUser) {
            self::loadPermissions((int) $fullUser['id_rol']);
        }
        self::syncPasswordFingerprint();

        if ($remember && !empty($user['id_usuario'])) {
            $issued = (new RememberToken())->issue((int)$user['id_usuario'], self::clientIp(), self::clientAgent());
            self::setRememberCookie($issued['cookie']);
        }
    }

    /**
     * Detecta HTTPS considerando despliegues detrás de un proxy reverso
     * (Apache/XAMPP directo expone $_SERVER['HTTPS']; un proxy TLS-terminating
     * reenvía el esquema original en X-Forwarded-Proto).
     */
    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * Intenta autenticar al usuario a partir de la cookie remember_token (`selector:validador`).
     * Rota solo el validador de ese dispositivo. Una cookie inválida solo se borra del cliente:
     * no se toca ninguna fila, para que una pestaña con cookie vieja no cierre el dispositivo.
     */
    public static function loginWithCookie(): bool
    {
        if (empty($_COOKIE['remember_token'])) {
            return false;
        }

        $parts = explode(':', (string)$_COOKIE['remember_token'], 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            self::clearRememberCookie();
            return false;
        }

        $result = (new RememberToken())->consume($parts[0], $parts[1], self::clientIp(), self::clientAgent());
        $user   = $result ? (new User())->find($result['id_usuario']) : null;
        if (!$result || !$user) {
            self::clearRememberCookie();
            return false;
        }

        self::login($user);
        self::setRememberCookie($result['cookie']);

        if ($result['ip_changed']) {
            ActivityLog::record(
                'login',
                'auth',
                (int)$user['id_usuario'],
                "Sesión recordada reanudada desde otra IP: {$user['email']}"
            );
        }

        return true;
    }

    /**
     * Cierra la sesión: revoca solo el "recordarme" de este dispositivo, borra la cookie y destruye la sesión.
     */
    public static function logout(): void
    {
        self::startSession();

        $selector = explode(':', (string)($_COOKIE['remember_token'] ?? ''), 2)[0];
        if ($selector !== '') {
            (new RememberToken())->revoke($selector);
        }

        self::clearRememberCookie();
        self::destroySession();
    }

    /** Vacía y destruye la sesión de PHP sin tocar los dispositivos "recordarme". */
    private static function destroySession(): void
    {
        self::startSession();
        self::$cachedUser = null;
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

    private static function setRememberCookie(string $value): void
    {
        setcookie('remember_token', $value, [
            'expires'  => time() + AuthSettings::rememberDays() * 86400,
            'path'     => '/',
            'secure'   => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    private static function clientIp(): string
    {
        return (string)($_SERVER['REMOTE_ADDR'] ?? '');
    }

    private static function clientAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
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
                'secure'   => self::isHttps(),
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

    /** Máximo de tokens de formulario pendientes por ámbito (pestañas abiertas a la vez). */
    private const FORM_TOKEN_LIMIT = 20;

    /**
     * Emite un token de un solo uso para un formulario que no debe procesarse dos veces
     * (doble clic, dos pestañas). Cada render emite el suyo, así varias pestañas abiertas
     * siguen siendo válidas. PHP serializa las peticiones de una misma sesión, por lo que dos
     * envíos simultáneos con el mismo token no pueden consumirlo a la vez.
     */
    public static function issueFormToken(string $scope): string
    {
        self::startSession();

        $token = bin2hex(random_bytes(16));
        $pending = $_SESSION['form_tokens'][$scope] ?? [];
        $pending[] = $token;
        $_SESSION['form_tokens'][$scope] = array_slice($pending, -self::FORM_TOKEN_LIMIT);

        return $token;
    }

    /**
     * Consume un token emitido con issueFormToken(). Devuelve false si ya se usó, venció la
     * sesión o nunca se emitió.
     */
    public static function consumeFormToken(string $scope, string $token): bool
    {
        self::startSession();

        $pending = $_SESSION['form_tokens'][$scope] ?? [];
        $index = array_search($token, $pending, true);
        if ($token === '' || $index === false) {
            return false;
        }

        unset($pending[$index]);
        $_SESSION['form_tokens'][$scope] = array_values($pending);

        return true;
    }

    /**
     * Valida el token CSRF recibido contra el almacenado en sesión usando comparación segura.
     *
     * @param string $token Token recibido desde el formulario.
     * @return bool True si el token es válido.
     */
    public static function validateCsrfToken(mixed $token): bool
    {
        self::startSession();

        return !empty($_SESSION['csrf_token'])
            && is_string($token)
            && $token !== ''
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

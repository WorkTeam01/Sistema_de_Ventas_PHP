<?php

namespace App\Core;

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION['sesion_email']);
    }

    public static function email(): ?string
    {
        self::startSession();
        return $_SESSION['sesion_email'] ?? null;
    }

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

    public static function role(): ?string
    {
        $user = self::user();
        return $user['rol'] ?? null;
    }

    public static function name(): ?string
    {
        $user = self::user();
        return $user['nombres'] ?? null;
    }

    public static function login(array $user): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['sesion_email'] = $user['email'] ?? null;
    }

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

    public static function generateCsrfToken(): string
    {
        self::startSession();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(string $token): bool
    {
        self::startSession();

        return !empty($_SESSION['csrf_token'])
            && !empty($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

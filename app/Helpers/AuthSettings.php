<?php

namespace App\Helpers;

use App\Core\Config;

/**
 * Umbrales de login, restablecimiento de contraseña y "recordarme" leídos del .env.
 * Un valor ausente, no entero o fuera de rango cae al valor por defecto, para que un .env
 * mal escrito no desactive la protección.
 */
final class AuthSettings
{
    public static function loginMaxPerEmail(): int
    {
        return Config::int('LOGIN_MAX_PER_EMAIL', 5, 1, 50);
    }

    public static function loginMaxPerIp(): int
    {
        return Config::int('LOGIN_MAX_PER_IP', 20, 1, 500);
    }

    public static function loginWindowMinutes(): int
    {
        return Config::int('LOGIN_WINDOW_MINUTES', 15, 1, 1440);
    }

    public static function resetTokenMinutes(): int
    {
        return Config::int('RESET_TOKEN_MINUTES', 60, 5, 1440);
    }

    public static function resetCooldownMinutes(): int
    {
        return Config::int('RESET_COOLDOWN_MINUTES', 5, 1, 60);
    }

    public static function resetMaxPerIpHour(): int
    {
        return Config::int('RESET_MAX_PER_IP_HOUR', 5, 1, 100);
    }

    public static function rememberDays(): int
    {
        return Config::int('REMEMBER_LIFETIME', 14, 1, 90);
    }
}

<?php

namespace App\Helpers;

use App\Core\Config;

/**
 * Decisiones sobre HTTPS y HSTS a partir de la configuración, nunca de cabeceras del cliente:
 * X-Forwarded-Proto y $_SERVER['HTTPS'] los puede falsear quien hace la petición.
 */
final class SecurityHeaders
{
    private const DEFAULT_HSTS_MAX_AGE = 86400;

    /** La aplicación se sirve por HTTPS cuando APP_URL declara ese esquema. */
    public static function isHttpsUrl(string $appUrl): bool
    {
        $scheme = parse_url($appUrl, PHP_URL_SCHEME);

        return is_string($scheme) && strtolower($scheme) === 'https';
    }

    /**
     * Valor de Strict-Transport-Security, o null si APP_URL no es HTTPS (el navegador ignora HSTS por
     * HTTP y un valor largo es difícil de revertir). Sin includeSubDomains ni preload: en hosting
     * compartido los subdominios pueden no tener HTTPS.
     *
     * Un max-age que no sea un entero no negativo usa el valor por defecto, para que un .env mal
     * escrito no desactive HSTS ni lo deje en un valor absurdo.
     */
    public static function hstsValue(string $appUrl, mixed $rawMaxAge): ?string
    {
        if (!self::isHttpsUrl($appUrl)) {
            return null;
        }

        $maxAge = self::DEFAULT_HSTS_MAX_AGE;

        if (is_int($rawMaxAge) && $rawMaxAge >= 0) {
            $maxAge = $rawMaxAge;
        } elseif (is_string($rawMaxAge) && preg_match('/^\d{1,10}$/', $rawMaxAge) === 1) {
            $maxAge = (int) $rawMaxAge;
        }

        return 'max-age=' . $maxAge;
    }

    /** Emite HSTS según APP_URL y HSTS_MAX_AGE del .env. */
    public static function send(): void
    {
        $hsts = self::hstsValue((string) Config::get('APP_URL', ''), Config::get('HSTS_MAX_AGE'));

        if ($hsts !== null) {
            header('Strict-Transport-Security: ' . $hsts);
        }
    }
}

<?php

namespace App\Core;

use Dotenv\Dotenv;

/**
 * Wrapper de variables de entorno cargadas desde el archivo .env con phpdotenv.
 *
 * Usar Config::get('CLAVE', $default) en cualquier parte del sistema.
 */
class Config
{
    private static bool $loaded = false;

    /**
     * Carga el archivo .env desde la raíz del proyecto usando phpdotenv.
     * Es idempotente: solo carga una vez por ciclo de vida de la petición.
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $rootPath = dirname(__DIR__, 2);
        $envFile = $rootPath . '/.env';

        if (is_file($envFile)) {
            $dotenv = Dotenv::createImmutable($rootPath);
            $dotenv->load();
        }

        self::$loaded = true;
    }

    /**
     * Devuelve el valor de una variable de entorno, cargando el .env si es necesario.
     *
     * @param string $key     Nombre de la variable (ej. 'DB_HOST').
     * @param mixed  $default Valor por defecto si la clave no existe.
     * @return mixed Valor de la variable o $default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        return $_ENV[$key] ?? $default;
    }

    /**
     * Devuelve una variable de entorno entera dentro de [$min, $max].
     * Si no existe, no es un entero o está fuera de rango, devuelve $default.
     */
    public static function int(string $key, int $default, int $min, int $max): int
    {
        $raw = self::get($key);
        if (!is_string($raw) && !is_int($raw)) {
            return $default;
        }

        $value = filter_var(trim((string)$raw), FILTER_VALIDATE_INT);

        return ($value === false || $value < $min || $value > $max) ? $default : $value;
    }
}

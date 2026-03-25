<?php

namespace App\Core;

use Dotenv\Dotenv;

class Config
{
    private static bool $loaded = false;

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

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        return $_ENV[$key] ?? $default;
    }
}

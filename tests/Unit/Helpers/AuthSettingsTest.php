<?php

namespace Tests\Unit\Helpers;

use App\Core\Config;
use App\Helpers\AuthSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthSettingsTest extends TestCase
{
    private const VARS = [
        'LOGIN_MAX_PER_EMAIL', 'LOGIN_MAX_PER_IP', 'LOGIN_WINDOW_MINUTES',
        'RESET_TOKEN_MINUTES', 'RESET_COOLDOWN_MINUTES', 'RESET_MAX_PER_IP_HOUR', 'REMEMBER_LIFETIME',
    ];

    private array $saved = [];

    protected function setUp(): void
    {
        // Cargar el .env real antes de limpiar: así un .env local no reaparece después del unset.
        Config::load();
        foreach (self::VARS as $var) {
            $this->saved[$var] = $_ENV[$var] ?? null;
            unset($_ENV[$var]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $var => $value) {
            if ($value === null) {
                unset($_ENV[$var]);
            } else {
                $_ENV[$var] = $value;
            }
        }
    }

    public static function settings(): array
    {
        // variable, getter, default, mínimo, máximo
        return [
            ['LOGIN_MAX_PER_EMAIL', 'loginMaxPerEmail', 5, 1, 50],
            ['LOGIN_MAX_PER_IP', 'loginMaxPerIp', 20, 1, 500],
            ['LOGIN_WINDOW_MINUTES', 'loginWindowMinutes', 15, 1, 1440],
            ['RESET_TOKEN_MINUTES', 'resetTokenMinutes', 60, 5, 1440],
            ['RESET_COOLDOWN_MINUTES', 'resetCooldownMinutes', 5, 1, 60],
            ['RESET_MAX_PER_IP_HOUR', 'resetMaxPerIpHour', 5, 1, 100],
            ['REMEMBER_LIFETIME', 'rememberDays', 14, 1, 90],
        ];
    }

    #[DataProvider('settings')]
    public function test_default_when_not_defined(string $var, string $getter, int $default): void
    {
        $this->assertSame($default, AuthSettings::$getter());
    }

    #[DataProvider('settings')]
    public function test_valid_value_and_range_edges_are_accepted(string $var, string $getter, int $default, int $min, int $max): void
    {
        foreach ([$min, $max] as $value) {
            $_ENV[$var] = (string)$value;
            $this->assertSame($value, AuthSettings::$getter());
        }
    }

    #[DataProvider('settings')]
    public function test_out_of_range_or_non_integer_falls_back_to_default(string $var, string $getter, int $default, int $min, int $max): void
    {
        foreach ([(string)($min - 1), (string)($max + 1), 'abc', '1.5', ''] as $bad) {
            $_ENV[$var] = $bad;
            $this->assertSame($default, AuthSettings::$getter(), "$var=$bad");
        }
    }
}

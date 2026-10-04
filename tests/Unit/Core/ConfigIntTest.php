<?php

namespace Tests\Unit\Core;

use App\Core\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConfigIntTest extends TestCase
{
    private const KEY = 'TEST_CONFIG_INT';

    protected function tearDown(): void
    {
        unset($_ENV[self::KEY]);
    }

    #[DataProvider('cases')]
    public function test_int_validates_range_and_falls_back_to_default(?string $raw, int $expected): void
    {
        if ($raw === null) {
            unset($_ENV[self::KEY]);
        } else {
            $_ENV[self::KEY] = $raw;
        }

        $this->assertSame($expected, Config::int(self::KEY, 5, 1, 50));
    }

    public static function cases(): array
    {
        return [
            'no definida' => [null, 5],
            'valor válido' => ['12', 12],
            'mínimo' => ['1', 1],
            'máximo' => ['50', 50],
            'por debajo del mínimo' => ['0', 5],
            'negativo' => ['-3', 5],
            'por encima del máximo' => ['51', 5],
            'no entero' => ['abc', 5],
            'decimal' => ['2.5', 5],
            'vacío' => ['', 5],
            'con espacios' => [' 7 ', 7],
        ];
    }
}

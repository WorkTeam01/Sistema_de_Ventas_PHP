<?php

namespace Tests\Unit\Helpers;

use App\Helpers\NumberToWords;
use Tests\TestCase;

final class NumberToWordsTest extends TestCase
{
    public function test_converts_zero(): void
    {
        $result = NumberToWords::convert(0);
        $this->assertStringContainsString('CERO', $result);
        $this->assertStringContainsString('00/100 Bs.', $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('singleDigitProvider')]
    public function test_converts_single_digits(float $number, string $expected): void
    {
        $result = NumberToWords::convert($number);
        $this->assertStringContainsString($expected, $result);
        $this->assertStringContainsString('Bs.', $result);
    }

    public static function singleDigitProvider(): array
    {
        return [
            'uno'    => [1,  'UN'],
            'dos'    => [2,  'DOS'],
            'tres'   => [3,  'TRES'],
            'cinco'  => [5,  'CINCO'],
            'nueve'  => [9,  'NUEVE'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tensProvider')]
    public function test_converts_tens_and_special_cases(float $number, string $expected): void
    {
        $result = NumberToWords::convert($number);
        $this->assertStringContainsString($expected, $result);
    }

    public static function tensProvider(): array
    {
        return [
            'diez'       => [10, 'DIEZ'],
            'once'       => [11, 'ONCE'],
            'quince'     => [15, 'QUINCE'],
            'dieciseis'  => [16, 'DIECISEIS'],
            'veinte'     => [20, 'VEINTE'],
            'treinta'    => [30, 'TREINTA'],
            'veintiuno'  => [21, 'VEINTE'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('hundredsProvider')]
    public function test_converts_hundreds(float $number, string $expected): void
    {
        $result = NumberToWords::convert($number);
        $this->assertStringContainsString($expected, $result);
    }

    public static function hundredsProvider(): array
    {
        return [
            'cien'        => [100, 'CIEN'],
            'ciento_uno'  => [101, 'CIENTO'],
            'quinientos'  => [500, 'QUINIENTOS'],
            'novecientos' => [900, 'NOVECIENTOS'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('thousandsProvider')]
    public function test_converts_thousands(float $number, string $expected): void
    {
        $result = NumberToWords::convert($number);
        $this->assertStringContainsString($expected, $result);
        $this->assertStringContainsString('MIL', $result);
    }

    public static function thousandsProvider(): array
    {
        return [
            'mil'       => [1000,  'UN'],
            'mil_500'   => [1500,  'QUINIENTOS'],
            'nueve_999' => [9999,  'NUEVE'],
        ];
    }

    public function test_converts_decimals(): void
    {
        $result = NumberToWords::convert(1.50);
        $this->assertStringContainsString('50/100 Bs.', $result);
    }

    public function test_decimals_with_cents(): void
    {
        $result = NumberToWords::convert(10.75);
        $this->assertStringContainsString('75/100 Bs.', $result);
    }

    public function test_handles_large_numbers(): void
    {
        $result = NumberToWords::convert(123456.78);
        $this->assertStringContainsString('78/100 Bs.', $result);
        $this->assertStringContainsString('MIL', $result);
    }

    public function test_output_always_ends_with_bs(): void
    {
        foreach ([0, 1, 50, 100, 1000, 99999] as $n) {
            $this->assertStringEndsWith('Bs.', NumberToWords::convert($n));
        }
    }

    public function test_output_contains_bs_suffix(): void
    {
        // El sufijo monetario siempre termina en "Bs." (con 's' minúscula por convención boliviana)
        $result = NumberToWords::convert(42);
        $this->assertStringEndsWith('Bs.', $result);
        $this->assertStringContainsString('CON', $result);
    }
}

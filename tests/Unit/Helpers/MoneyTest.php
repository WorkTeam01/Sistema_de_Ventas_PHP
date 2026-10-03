<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public static function validAmounts(): array
    {
        return [
            'entero'                     => ['100', 10000],
            'punto'                      => ['12.50', 1250],
            'coma'                       => ['12,50', 1250],
            'un decimal'                 => ['0.5', 50],
            'cero'                       => ['0', 0],
            'tercer decimal 5 sube'      => ['10.005', 1001],
            'tercer decimal 4 baja'      => ['10.004', 1000],
            'cuarto decimal no cuenta'   => ['10.0049', 1000],
            'redondeo cruza el entero'   => ['0.995', 100],
            'espacios alrededor'         => [' 7.25 ', 725],
            'int'                        => [5, 500],
            'float'                      => [33.30, 3330],
            'float mitad hacia arriba'   => [10.005, 1001],
            'maximo de la venta'         => ['99999999.99', 9999999999],
        ];
    }

    #[DataProvider('validAmounts')]
    public function testToCentsParsesValidAmounts(string|int|float $input, int $expected): void
    {
        $this->assertSame($expected, Money::toCents($input));
    }

    public static function invalidAmounts(): array
    {
        return [
            'vacio'              => [''],
            'texto'              => ['abc'],
            'negativo'           => ['-5'],
            'negativo float'     => [-1.5],
            'notacion cientifica' => ['1e3'],
            'dos separadores'    => ['1.234,50'],
            'punto final'        => ['10.'],
            'punto inicial'      => ['.5'],
            'simbolo'            => ['Bs. 10'],
            'nan'                => [NAN],
            'infinito'           => [INF],
            'desborda'           => ['99999999999999999999'],
        ];
    }

    #[DataProvider('invalidAmounts')]
    public function testToCentsReturnsNullForInvalidInput(string|int|float $input): void
    {
        $this->assertNull(Money::toCents($input));
    }

    public function testFromCentsFormatsTwoDecimals(): void
    {
        $this->assertSame('1234.50', Money::fromCents(123450));
        $this->assertSame('0.05', Money::fromCents(5));
        $this->assertSame('0.00', Money::fromCents(0));
        $this->assertSame('-3.07', Money::fromCents(-307));
    }

    public function testSubtractionIsExactWhereFloatIsNot(): void
    {
        $this->assertNotSame(22.20, 33.30 - 11.10);
        $this->assertSame(
            Money::toCents('22.20'),
            Money::toCents(33.30) - Money::toCents(11.10)
        );
    }

    public function testTwoRoundedLinesAreSummedAfterRounding(): void
    {
        $sum = Money::toCents('10.004') + Money::toCents('10.004');

        $this->assertSame(2000, $sum);
        $this->assertSame('20.00', Money::fromCents($sum));
    }

    public function testRoundTrip(): void
    {
        $this->assertSame('99.99', Money::fromCents(Money::toCents('99,99')));
    }
}

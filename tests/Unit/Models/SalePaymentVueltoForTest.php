<?php

namespace Tests\Unit\Models;

use App\Models\SalePayment;
use Tests\TestCase;

final class SalePaymentVueltoForTest extends TestCase
{
    public function test_sum_equals_total_returns_zero(): void
    {
        $result = SalePayment::vueltoFor(100.00, [
            ['monto' => 60.00],
            ['monto' => 40.00],
        ]);

        $this->assertSame(0.0, $result);
    }

    public function test_sum_greater_than_total_returns_positive_difference(): void
    {
        $result = SalePayment::vueltoFor(100.00, [
            ['monto' => 150.00],
        ]);

        $this->assertSame(50.0, $result);
    }

    public function test_sum_less_than_total_returns_zero(): void
    {
        $result = SalePayment::vueltoFor(100.00, [
            ['monto' => 30.00],
            ['monto' => 40.00],
        ]);

        $this->assertSame(0.0, $result);
    }

    public function test_empty_payments_returns_zero(): void
    {
        $this->assertSame(0.0, SalePayment::vueltoFor(50.00, []));
    }

    public function test_rounds_sum_to_two_decimals_before_subtracting(): void
    {
        // 10.10 + 10.20 = 20.30 − 20.00 = 0.30
        $result = SalePayment::vueltoFor(20.00, [
            ['monto' => 10.10],
            ['monto' => 10.20],
        ]);

        $this->assertEqualsWithDelta(0.30, $result, 0.001);
    }

    public function test_missing_monto_key_is_treated_as_zero(): void
    {
        $result = SalePayment::vueltoFor(50.00, [
            ['tipo' => 'efectivo'],
            ['monto' => 50.00],
        ]);

        $this->assertSame(0.0, $result);
    }

    public function test_mixed_efectivo_exceso_returns_change(): void
    {
        // Efectivo 80 + Tarjeta 30 = 110 − 100 = 10
        $result = SalePayment::vueltoFor(100.00, [
            ['monto' => 80.00, 'tipo' => 'efectivo'],
            ['monto' => 30.00, 'tipo' => 'no_efectivo'],
        ]);

        $this->assertSame(10.0, $result);
    }
}

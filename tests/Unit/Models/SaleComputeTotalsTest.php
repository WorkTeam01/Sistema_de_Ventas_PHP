<?php

namespace Tests\Unit\Models;

use App\Models\Sale;
use Tests\TestCase;

final class SaleComputeTotalsTest extends TestCase
{
    private Sale $sale;

    protected function setUp(): void
    {
        $this->sale = new Sale();
    }

    public function test_empty_cart_returns_zero_totals(): void
    {
        $result = $this->sale->computeInvoiceTotals([]);

        $this->assertSame(0.0, $result['precio_total']);
        $this->assertSame(0,   $result['cantidad_total']);
        $this->assertSame(0.0, $result['total_unitarios']);
    }

    public function test_returned_structure_has_expected_keys(): void
    {
        $result = $this->sale->computeInvoiceTotals([]);

        $this->assertArrayHasKey('precio_total',    $result);
        $this->assertArrayHasKey('cantidad_total',  $result);
        $this->assertArrayHasKey('total_unitarios', $result);
    }

    public function test_single_item_computes_subtotal(): void
    {
        $items = [
            ['cantidad' => 2, 'precio_venta' => 10.00],
        ];

        $result = $this->sale->computeInvoiceTotals($items);

        $this->assertSame(20.0, $result['precio_total']);
        $this->assertSame(2,    $result['cantidad_total']);
        $this->assertSame(10.0, $result['total_unitarios']);
    }

    public function test_multiple_items_sums_correctly(): void
    {
        $items = [
            ['cantidad' => 1, 'precio_venta' => 5.00],
            ['cantidad' => 2, 'precio_venta' => 3.50],
        ];

        $result = $this->sale->computeInvoiceTotals($items);

        $this->assertSame(12.0, $result['precio_total']);    // 1*5 + 2*3.5
        $this->assertSame(3,    $result['cantidad_total']);   // 1 + 2
        $this->assertSame(8.5,  $result['total_unitarios']); // 5 + 3.5
    }

    public function test_decimal_precision(): void
    {
        $items = [
            ['cantidad' => 3, 'precio_venta' => 1.10],
        ];

        $result = $this->sale->computeInvoiceTotals($items);

        $this->assertEqualsWithDelta(3.30, $result['precio_total'], 0.001);
    }

    public function test_quantity_zero_yields_zero_line_total(): void
    {
        $items = [
            ['cantidad' => 0, 'precio_venta' => 50.00],
        ];

        $result = $this->sale->computeInvoiceTotals($items);

        $this->assertSame(0.0,  $result['precio_total']);
        $this->assertSame(0,    $result['cantidad_total']);
        $this->assertSame(50.0, $result['total_unitarios']); // precio_venta se suma igual
    }

    public function test_precio_total_is_float(): void
    {
        $result = $this->sale->computeInvoiceTotals([
            ['cantidad' => 1, 'precio_venta' => 100.00],
        ]);

        $this->assertIsFloat($result['precio_total']);
    }

    public function test_cantidad_total_is_int(): void
    {
        $result = $this->sale->computeInvoiceTotals([
            ['cantidad' => 3, 'precio_venta' => 10.00],
        ]);

        $this->assertIsInt($result['cantidad_total']);
    }
}

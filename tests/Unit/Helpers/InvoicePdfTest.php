<?php

namespace Tests\Unit\Helpers;

use App\Helpers\InvoicePdf;
use PHPUnit\Framework\TestCase;

class InvoicePdfTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('APP_CURRENCY_SYMBOL')) {
            define('APP_CURRENCY_SYMBOL', 'Bs.');
        }
    }

    private function html(float $total, float $ajuste): string
    {
        $items = [['nombre' => 'Prod', 'descripcion' => 'Desc', 'cantidad' => 2, 'precio_venta' => 10.00]];
        $method = new \ReflectionMethod(InvoicePdf::class, 'buildHtml');

        return $method->invoke(
            null,
            '/logo.jpg',
            7,
            '03/10/2026',
            'Cliente',
            '123',
            $items,
            $total,
            2,
            10.0,
            'Veinte',
            'Vendedor',
            [['nombre' => 'Efectivo', 'monto' => $total]],
            0.0,
            $ajuste
        );
    }

    public function testNoAdjustmentRowWhenTheLinesMatchTheTotal(): void
    {
        $html = $this->html(20.00, 0.0);

        $this->assertStringNotContainsString('Ajuste por precios históricos', $html);
        $this->assertStringContainsString('Bs. 20.00', $html);
    }

    public function testPositiveAdjustmentRowAndTotalIsTotalPagado(): void
    {
        $html = $this->html(25.00, 5.00);

        $this->assertStringContainsString('Ajuste por precios históricos', $html);
        $this->assertStringContainsString('Bs. 5.00', $html);
        $this->assertStringContainsString('Bs. 25.00', $html);
    }

    public function testNegativeAdjustmentIsShownWithItsSign(): void
    {
        $html = $this->html(17.50, -2.50);

        $this->assertStringContainsString('Ajuste por precios históricos', $html);
        $this->assertStringContainsString('-Bs. 2.50', $html);
        $this->assertStringContainsString('Bs. 17.50', $html);
    }
}

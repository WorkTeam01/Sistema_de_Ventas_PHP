<?php

namespace Tests\Integration\Models;

use App\Models\Report;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class ReportSalesTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Report $report;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->report = new Report();
        $this->seedDependencies();
    }

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
                          VALUES ('Juan Perez', '12345678', '70000001', 'juan@test.com'),
                                 ('Maria Lopez', '87654321', '70000002', 'maria@test.com')");
    }

    private function seedSale(int $nroVenta, float $total, string $fecha, int $idCliente = 1): void
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, total_pagado, fyh_creacion)
                          VALUES ({$nroVenta}, {$idCliente}, {$total}, '{$fecha}')");
    }

    // ── salesByPeriod ─────────────────────────────────────────────────────────

    public function test_salesByPeriod_returns_sales_within_range(): void
    {
        $this->seedSale(1, 100.00, '2025-06-10 10:00:00');
        $this->seedSale(2, 200.00, '2025-06-15 12:00:00');
        $this->seedSale(3, 300.00, '2025-07-01 09:00:00'); // fuera de rango

        $rows = $this->report->salesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(2, $rows);
    }

    public function test_salesByPeriod_returns_empty_when_no_sales_in_range(): void
    {
        $this->seedSale(1, 100.00, '2025-05-01 10:00:00');

        $rows = $this->report->salesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(0, $rows);
    }

    public function test_salesByPeriod_includes_client_name(): void
    {
        $this->seedSale(1, 50.00, '2025-06-10 10:00:00', 1);

        $rows = $this->report->salesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame('Juan Perez', $rows[0]['cliente']);
    }

    public function test_salesByPeriod_ordered_by_date_descending(): void
    {
        $this->seedSale(1, 50.00, '2025-06-05 10:00:00');
        $this->seedSale(2, 80.00, '2025-06-20 10:00:00');

        $rows = $this->report->salesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(2, (int)$rows[0]['nro_venta']);
        $this->assertSame(1, (int)$rows[1]['nro_venta']);
    }

    // ── salesTotals ───────────────────────────────────────────────────────────

    public function test_salesTotals_sums_correctly(): void
    {
        $this->seedSale(1, 100.00, '2025-06-10 10:00:00');
        $this->seedSale(2, 200.00, '2025-06-15 12:00:00');

        $totals = $this->report->salesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(2, (int)$totals['num_ventas']);
        $this->assertEqualsWithDelta(300.00, (float)$totals['total_ingresos'], 0.01);
        $this->assertEqualsWithDelta(150.00, (float)$totals['ticket_promedio'], 0.01);
    }

    public function test_salesTotals_returns_zeros_when_no_sales(): void
    {
        $totals = $this->report->salesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(0, (int)$totals['num_ventas']);
        $this->assertEqualsWithDelta(0.0, (float)$totals['total_ingresos'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float)$totals['ticket_promedio'], 0.01);
    }

    public function test_salesTotals_excludes_sales_outside_range(): void
    {
        $this->seedSale(1, 500.00, '2025-05-01 10:00:00'); // fuera
        $this->seedSale(2, 100.00, '2025-06-10 10:00:00'); // dentro

        $totals = $this->report->salesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(1, (int)$totals['num_ventas']);
        $this->assertEqualsWithDelta(100.00, (float)$totals['total_ingresos'], 0.01);
    }
}

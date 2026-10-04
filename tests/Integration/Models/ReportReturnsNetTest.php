<?php

namespace Tests\Integration\Models;

use App\Models\Report;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

/**
 * V7 — Neto de reportes (FR-21): salesByPeriod, salesTotals, salesSummary,
 *       topProducts, clientsByPeriod descuentan devoluciones imputadas al período.
 *       Fechas literales en SQLite (sin CURDATE/YEAR/MONTH).
 */
final class ReportReturnsNetTest extends TestCase
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
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
                          VALUES ('Cliente Alpha', '11111111', '70000001', 'alpha@test.com'),
                                 ('Cliente Beta',  '22222222', '70000002', 'beta@test.com')");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_almacen
                          (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
                          VALUES
                          ('P001', 'Producto A', 100, 10.00, 20.00, '2025-01-01', 1, 1),
                          ('P002', 'Producto B', 100, 10.00, 30.00, '2025-01-01', 1, 1)");
    }

    private function seedVenta(int $nroVenta, int $idCliente, float $total, string $fecha, int $idUsuario = 1): void
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
                          VALUES ({$nroVenta}, {$idCliente}, {$idUsuario}, {$total}, '{$fecha}')");
    }

    private function seedVentaConItems(int $nroVenta, int $idCliente, string $fecha, array $items, int $idUsuario = 1): void
    {
        $this->seedVenta($nroVenta, $idCliente, 0, $fecha, $idUsuario);
        foreach ($items as [$idProducto, $cantidad, $precio]) {
            $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario)
                              VALUES ({$nroVenta}, {$idProducto}, {$cantidad}, {$precio})");
        }
    }

    private function seedDevolucion(int $idVenta, int $idUsuario, float $monto, string $fecha, array $items): void
    {
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto, fyh_creacion)
                          VALUES (0, {$idVenta}, {$idUsuario}, 'Devolución test', {$monto}, '{$fecha}')");
        $idDevolucion = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("UPDATE tb_devoluciones SET nro_devolucion = {$idDevolucion} WHERE id_devolucion = {$idDevolucion}");
        foreach ($items as [$idProducto, $cantidad, $precio]) {
            $this->pdo->exec("INSERT INTO tb_devolucion_items (id_devolucion, id_producto, cantidad, precio_unitario)
                              VALUES ({$idDevolucion}, {$idProducto}, {$cantidad}, {$precio})");
        }
    }

    // ── salesByPeriod net ────────────────────────────────────────────────────

    public function test_salesByPeriod_subtracts_returns_in_same_period(): void
    {
        // Venta 100 + Venta 200 en junio; devolución 30 en junio
        $this->seedVenta(1, 1, 100.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-15 12:00:00');
        $this->seedDevolucion(1, 1, 30.00, '2025-06-20 09:00:00', [[1, 1, 30.00]]);

        $rows = $this->report->salesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(2, $rows);
        // La venta devuelta tiene monto_devuelto = 30
        $devuelta = array_filter($rows, fn($r) => (int)$r['id_venta'] === 1);
        $devuelta = reset($devuelta);
        $this->assertEqualsWithDelta(30.00, (float)$devuelta['monto_devuelto'], 0.01);
        // La otra venta no tiene devoluciones
        $otra = array_filter($rows, fn($r) => (int)$r['id_venta'] === 2);
        $otra = reset($otra);
        $this->assertEqualsWithDelta(0.00, (float)$otra['monto_devuelto'], 0.01);
    }

    public function test_salesByPeriod_return_outside_period_not_subtracted(): void
    {
        // Venta en junio, devolución en julio
        $this->seedVenta(1, 1, 100.00, '2025-06-10 10:00:00');
        $this->seedDevolucion(1, 1, 30.00, '2025-07-05 09:00:00', [[1, 1, 30.00]]);

        $rows = $this->report->salesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(0.00, (float)$rows[0]['monto_devuelto'], 0.01);
    }

    // ── salesTotals net ──────────────────────────────────────────────────────

    public function test_salesTotals_subtracts_returns(): void
    {
        // Ventas: 100 + 200 = 300; devolución: 30 → neto = 270
        $this->seedVenta(1, 1, 100.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-15 12:00:00');
        $this->seedDevolucion(1, 1, 30.00, '2025-06-20 09:00:00', [[1, 1, 30.00]]);

        $totals = $this->report->salesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(2, (int)$totals['num_ventas']);
        $this->assertEqualsWithDelta(270.00, (float)$totals['total_ingresos'], 0.01);
    }

    public function test_salesTotals_num_ventas_intact_with_returns(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-06-10 10:00:00');
        $this->seedDevolucion(1, 1, 100.00, '2025-06-20 09:00:00', [[1, 1, 100.00]]);

        $totals = $this->report->salesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(1, (int)$totals['num_ventas']);
        $this->assertEqualsWithDelta(0.00, (float)$totals['total_ingresos'], 0.01);
    }

    public function test_salesTotals_no_returns_gross_unchanged(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-15 12:00:00');

        $totals = $this->report->salesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(2, (int)$totals['num_ventas']);
        $this->assertEqualsWithDelta(300.00, (float)$totals['total_ingresos'], 0.01);
    }

    // ── salesSummary net ─────────────────────────────────────────────────────

    public function test_salesSummary_subtracts_returns(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-15 12:00:00');
        $this->seedDevolucion(2, 1, 50.00, '2025-06-20 09:00:00', [[2, 1, 50.00]]);

        $summary = $this->report->salesSummary('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(2, (int)$summary['num_ventas']);
        $this->assertEqualsWithDelta(250.00, (float)$summary['total_ingresos'], 0.01);
    }

    public function test_salesSummary_num_ventas_intact(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-06-10 10:00:00');
        $this->seedDevolucion(1, 1, 100.00, '2025-06-20 09:00:00', [[1, 1, 100.00]]);

        $summary = $this->report->salesSummary('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(1, (int)$summary['num_ventas']);
        $this->assertEqualsWithDelta(0.00, (float)$summary['total_ingresos'], 0.01);
    }

    // ── Fila de períodos anteriores y ticket neto ────────────────────────────

    public function test_returnsOutsideListed_only_counts_returns_of_unlisted_sales(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-05-10 10:00:00'); // período anterior
        $this->seedVenta(2, 1, 200.00, '2025-06-15 12:00:00');
        $this->seedDevolucion(1, 1, 30.00, '2025-06-20 09:00:00', [[1, 1, 30.00]]);
        $this->seedDevolucion(2, 1, 50.00, '2025-06-21 09:00:00', [[1, 1, 50.00]]);

        $outside = $this->report->returnsOutsideListed('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertEqualsWithDelta(30.00, $outside, 0.01);
    }

    public function test_listed_rows_plus_outside_row_sum_to_total(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-05-10 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-15 12:00:00');
        $this->seedDevolucion(1, 1, 30.00, '2025-06-20 09:00:00', [[1, 1, 30.00]]);
        $this->seedDevolucion(2, 1, 50.00, '2025-06-21 09:00:00', [[1, 1, 50.00]]);
        $from = '2025-06-01 00:00:00';
        $to   = '2025-06-30 23:59:59';

        $rows  = $this->report->salesByPeriod($from, $to);
        $listed = array_sum(array_map(fn($r) => (float)$r['total_pagado'] - (float)$r['monto_devuelto'], $rows));
        $total = (float)$this->report->salesTotals($from, $to)['total_ingresos'];

        $this->assertEqualsWithDelta($total, $listed - $this->report->returnsOutsideListed($from, $to), 0.01);
        $this->assertEqualsWithDelta(120.00, $total, 0.01);
    }

    public function test_returnsOutsideListed_respects_user_scope(): void
    {
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('Otro', 'otro@test.com', 'hash', 1)");
        $this->seedVenta(1, 1, 100.00, '2025-05-10 10:00:00', 1);
        $this->seedVenta(2, 1, 100.00, '2025-05-11 10:00:00', 2);
        $this->seedDevolucion(1, 1, 30.00, '2025-06-20 09:00:00', [[1, 1, 30.00]]);
        $this->seedDevolucion(2, 2, 10.00, '2025-06-20 10:00:00', [[1, 1, 10.00]]);

        $this->assertEqualsWithDelta(30.00, $this->report->returnsOutsideListed('2025-06-01 00:00:00', '2025-06-30 23:59:59', 1), 0.01);
        $this->assertEqualsWithDelta(40.00, $this->report->returnsOutsideListed('2025-06-01 00:00:00', '2025-06-30 23:59:59'), 0.01);
    }

    public function test_withNetAmounts_adds_net_per_row_and_rows_plus_outside_match_total(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-05-10 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-15 12:00:00');
        $this->seedDevolucion(1, 1, 30.00, '2025-06-20 09:00:00', [[1, 1, 30.00]]);
        $this->seedDevolucion(2, 1, 50.00, '2025-06-21 09:00:00', [[1, 1, 50.00]]);
        $from = '2025-06-01 00:00:00';
        $to   = '2025-06-30 23:59:59';

        $rows = $this->report->withNetAmounts($this->report->salesByPeriod($from, $to));

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(150.00, $rows[0]['total_neto'], 0.001);
        $this->assertEqualsWithDelta(
            (float)$this->report->salesTotals($from, $to)['total_ingresos'],
            array_sum(array_column($rows, 'total_neto')) - $this->report->returnsOutsideListed($from, $to),
            0.001
        );
    }

    public function test_salesTotals_ticket_is_net_total_over_sales_count(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-15 12:00:00');
        $this->seedDevolucion(1, 1, 40.00, '2025-06-20 09:00:00', [[1, 1, 40.00]]);

        $totals = $this->report->salesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertEqualsWithDelta(130.00, (float)$totals['ticket_promedio'], 0.01);
    }

    public function test_salesTotals_ticket_is_zero_without_sales(): void
    {
        $totals = $this->report->salesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(0, (int)$totals['num_ventas']);
        $this->assertEqualsWithDelta(0.00, (float)$totals['ticket_promedio'], 0.001);
    }

    // ── topProducts net ──────────────────────────────────────────────────────

    public function test_topProducts_subtracts_returned_quantities_and_amounts(): void
    {
        // Producto A: vendido 5 × 20 = 100; devuelto 2 × 20 = 40 → neto: 3 uds, 60 ingresos
        $this->seedVentaConItems(1, 1, '2025-06-10 10:00:00', [[1, 5, 20.00]]);
        $this->seedDevolucion(1, 1, 40.00, '2025-06-20 09:00:00', [[1, 2, 20.00]]);

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertSame(3, (int)$rows[0]['unidades_vendidas']);
        $this->assertEqualsWithDelta(60.00, (float)$rows[0]['ingresos'], 0.01);
    }

    public function test_topProducts_return_outside_period_not_subtracted(): void
    {
        // Venta en junio, devolución en julio → junio muestra bruto
        $this->seedVentaConItems(1, 1, '2025-06-10 10:00:00', [[1, 5, 20.00]]);
        $this->seedDevolucion(1, 1, 40.00, '2025-07-05 09:00:00', [[1, 2, 20.00]]);

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertSame(5, (int)$rows[0]['unidades_vendidas']);
        $this->assertEqualsWithDelta(100.00, (float)$rows[0]['ingresos'], 0.01);
    }

    public function test_topProducts_full_return_excluded_by_having(): void
    {
        // Producto A: vendido 3, devuelto 3 → unidades netas = 0 → excluir por HAVING
        $this->seedVentaConItems(1, 1, '2025-06-10 10:00:00', [[1, 3, 20.00]]);
        $this->seedDevolucion(1, 1, 60.00, '2025-06-20 09:00:00', [[1, 3, 20.00]]);

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(0, $rows);
    }

    public function test_topProducts_multiple_products_with_returns(): void
    {
        // A: 5 vendidos - 2 devueltos = 3; B: 10 vendidos - 0 devueltos = 10
        $this->seedVentaConItems(1, 1, '2025-06-10 10:00:00', [[1, 5, 20.00], [2, 10, 30.00]]);
        $this->seedDevolucion(1, 1, 40.00, '2025-06-20 09:00:00', [[1, 2, 20.00]]);

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59', 10, 'cantidad');

        $this->assertCount(2, $rows);
        // B primero (10 uds), A segundo (3 uds)
        $this->assertSame('Producto B', $rows[0]['nombre']);
        $this->assertSame(10, (int)$rows[0]['unidades_vendidas']);
        $this->assertSame('Producto A', $rows[1]['nombre']);
        $this->assertSame(3, (int)$rows[1]['unidades_vendidas']);
        $this->assertEqualsWithDelta(60.00, (float)$rows[1]['ingresos'], 0.01);
    }

    // ── clientsByPeriod net ──────────────────────────────────────────────────

    public function test_clientsByPeriod_subtracts_returns_per_client(): void
    {
        // Alpha: venta 300, devolución 50 → neto 250; Beta: venta 500, sin devolución
        $this->seedVenta(1, 1, 300.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 2, 500.00, '2025-06-15 10:00:00');
        $this->seedDevolucion(1, 1, 50.00, '2025-06-20 09:00:00', [[1, 1, 50.00]]);

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(2, $rows);
        $this->assertSame('Cliente Beta', $rows[0]['cliente']);
        $this->assertEqualsWithDelta(500.00, (float)$rows[0]['monto_acumulado'], 0.01);
        $this->assertSame('Cliente Alpha', $rows[1]['cliente']);
        $this->assertEqualsWithDelta(250.00, (float)$rows[1]['monto_acumulado'], 0.01);
    }

    public function test_clientsByPeriod_num_compras_intact(): void
    {
        // Alpha: 2 ventas, 1 devolución → num_compras sigue siendo 2
        $this->seedVenta(1, 1, 200.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 1, 100.00, '2025-06-15 10:00:00');
        $this->seedDevolucion(1, 1, 50.00, '2025-06-20 09:00:00', [[1, 1, 50.00]]);

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertSame(2, (int)$rows[0]['num_compras']);
        $this->assertEqualsWithDelta(250.00, (float)$rows[0]['monto_acumulado'], 0.01);
    }

    public function test_clientsByPeriod_return_outside_period_not_subtracted(): void
    {
        // Venta en junio, devolución en julio → junio muestra bruto
        $this->seedVenta(1, 1, 300.00, '2025-06-10 10:00:00');
        $this->seedDevolucion(1, 1, 50.00, '2025-07-05 09:00:00', [[1, 1, 50.00]]);

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(300.00, (float)$rows[0]['monto_acumulado'], 0.01);
    }

    public function test_clientsByPeriod_full_return_zero_monto(): void
    {
        // Alpha: venta 200, devolución 200 → neto 0
        $this->seedVenta(1, 1, 200.00, '2025-06-10 10:00:00');
        $this->seedDevolucion(1, 1, 200.00, '2025-06-20 09:00:00', [[1, 1, 200.00]]);

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertEqualsWithDelta(0.00, (float)$rows[0]['monto_acumulado'], 0.01);
        $this->assertSame(1, (int)$rows[0]['num_compras']);
    }
}

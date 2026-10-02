<?php

namespace Tests\Integration\Models;

use App\Models\Report;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

/**
 * Scoping de reportes por usuario: sin el permiso *_all el listado, el detalle
 * y los totales deben acotarse a los registros propios. Fechas literales en
 * SQLite (sin CURDATE/YEAR/MONTH).
 */
final class ReportScopingTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private const DESDE = '2025-06-01 00:00:00';
    private const HASTA = '2025-06-30 23:59:59';

    private Report $report;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->report = new Report();
        $this->seedDependencies();
    }

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador'), ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('Admin',  'admin@test.com',  'hash', 1),
                                 ('Vendedor', 'vend@test.com', 'hash', 2)");
        $this->pdo->exec("INSERT INTO tb_proveedores (nombre_proveedor, celular, empresa, direccion)
                          VALUES ('Distribuidora ABC', '70000001', 'ABC S.R.L.', 'Calle 1')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
                          VALUES ('Cliente Único', '11111111', '70000001', 'cliente@test.com')");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_almacen
                          (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
                          VALUES ('P001', 'Producto A', 100, 10.00, 20.00, '2025-01-01', 1, 1)");
    }

    private function seedCompra(int $nroCompra, int $idUsuario, float $precio = 100.00, int $cantidad = 2): void
    {
        $this->pdo->exec("INSERT INTO tb_compras
                          (nro_compra, id_producto, fecha_compra, id_proveedor, comprobante,
                           id_usuario, precio_compra, cantidad, fyh_creacion)
                          VALUES ({$nroCompra}, 1, '2025-06-10', 1, 'FAC-{$nroCompra}',
                                  {$idUsuario}, {$precio}, {$cantidad}, '2025-06-10 10:00:00')");
    }

    private function seedVentaConItems(int $nroVenta, int $idUsuario, string $fecha, array $items): void
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item[1] * $item[2];
        }

        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
                          VALUES ({$nroVenta}, 1, {$idUsuario}, {$total}, '{$fecha}')");
        foreach ($items as [$idProducto, $cantidad, $precio]) {
            $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario)
                              VALUES ({$nroVenta}, {$idProducto}, {$cantidad}, {$precio})");
        }
    }

    private function seedDevolucion(int $idVenta, int $idUsuario, float $monto, array $items): void
    {
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto, fyh_creacion)
                          VALUES (0, {$idVenta}, {$idUsuario}, 'Devolución test', {$monto}, '2025-06-15 10:00:00')");
        $idDevolucion = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("UPDATE tb_devoluciones SET nro_devolucion = {$idDevolucion} WHERE id_devolucion = {$idDevolucion}");
        foreach ($items as [$idProducto, $cantidad, $precio]) {
            $this->pdo->exec("INSERT INTO tb_devolucion_items (id_devolucion, id_producto, cantidad, precio_unitario)
                              VALUES ({$idDevolucion}, {$idProducto}, {$cantidad}, {$precio})");
        }
    }

    // ── Compras ──────────────────────────────────────────────────────────────

    public function test_purchasesByPeriod_filters_by_user(): void
    {
        $this->seedCompra(1, 1);
        $this->seedCompra(2, 2);

        $rows = $this->report->purchasesByPeriod(self::DESDE, self::HASTA, 2);

        $this->assertCount(1, $rows);
        $this->assertSame(2, (int)$rows[0]['nro_compra']);
    }

    public function test_purchasesByPeriod_returns_all_when_user_is_null(): void
    {
        $this->seedCompra(1, 1);
        $this->seedCompra(2, 2);

        $this->assertCount(2, $this->report->purchasesByPeriod(self::DESDE, self::HASTA));
    }

    public function test_purchasesTotals_filters_by_user(): void
    {
        $this->seedCompra(1, 1, 100.00, 2);
        $this->seedCompra(2, 2, 50.00, 4);

        $totals = $this->report->purchasesTotals(self::DESDE, self::HASTA, 2);

        $this->assertSame(1, (int)$totals['num_compras']);
        $this->assertSame(200.0, (float)$totals['total_egresos']);
    }

    public function test_purchasesTotals_returns_all_when_user_is_null(): void
    {
        $this->seedCompra(1, 1, 100.00, 2);
        $this->seedCompra(2, 2, 50.00, 4);

        $totals = $this->report->purchasesTotals(self::DESDE, self::HASTA);

        $this->assertSame(2, (int)$totals['num_compras']);
        $this->assertSame(400.0, (float)$totals['total_egresos']);
    }

    // ── Top productos ────────────────────────────────────────────────────────

    public function test_topProducts_filters_by_user(): void
    {
        $this->seedVentaConItems(1, 1, '2025-06-10 10:00:00', [[1, 3, 20.00]]);
        $this->seedVentaConItems(2, 2, '2025-06-11 10:00:00', [[1, 5, 20.00]]);

        $scoped   = $this->report->topProducts(self::DESDE, self::HASTA, 10, 'cantidad', 0, 1);
        $unscoped = $this->report->topProducts(self::DESDE, self::HASTA);

        $this->assertSame(3, (int)$scoped[0]['unidades_vendidas']);
        $this->assertSame(8, (int)$unscoped[0]['unidades_vendidas']);
    }

    public function test_topProducts_return_scope_applies_to_returns(): void
    {
        $this->seedVentaConItems(1, 1, '2025-06-10 10:00:00', [[1, 3, 20.00]]);
        $this->seedVentaConItems(2, 2, '2025-06-11 10:00:00', [[1, 5, 20.00]]);
        $this->seedDevolucion(2, 2, 40.00, [[1, 2, 20.00]]);

        $ownerOne   = $this->report->topProducts(self::DESDE, self::HASTA, 10, 'cantidad', 0, 1);
        $ownerTwo   = $this->report->topProducts(self::DESDE, self::HASTA, 10, 'cantidad', 0, 2);
        $unscoped   = $this->report->topProducts(self::DESDE, self::HASTA);

        $this->assertSame(3, (int)$ownerOne[0]['unidades_vendidas']);
        $this->assertSame(3, (int)$ownerTwo[0]['unidades_vendidas']);
        $this->assertSame(6, (int)$unscoped[0]['unidades_vendidas']);
    }

    // ── Clientes ─────────────────────────────────────────────────────────────

    public function test_clientsByPeriod_filters_by_user(): void
    {
        $this->seedVentaConItems(1, 1, '2025-06-10 10:00:00', [[1, 3, 20.00]]);
        $this->seedVentaConItems(2, 2, '2025-06-11 10:00:00', [[1, 5, 20.00]]);
        $this->seedDevolucion(2, 2, 40.00, [[1, 2, 20.00]]);

        $ownerOne = $this->report->clientsByPeriod(self::DESDE, self::HASTA, 1);
        $ownerTwo = $this->report->clientsByPeriod(self::DESDE, self::HASTA, 2);
        $all      = $this->report->clientsByPeriod(self::DESDE, self::HASTA);

        $this->assertSame(1, (int)$ownerOne[0]['num_compras']);
        $this->assertSame(60.0, (float)$ownerOne[0]['monto_acumulado']);

        $this->assertSame(1, (int)$ownerTwo[0]['num_compras']);
        $this->assertSame(60.0, (float)$ownerTwo[0]['monto_acumulado']);

        $this->assertSame(2, (int)$all[0]['num_compras']);
        $this->assertSame(120.0, (float)$all[0]['monto_acumulado']);
    }

    public function test_clientsByPeriod_excludes_clients_of_other_users(): void
    {
        $this->seedVentaConItems(1, 1, '2025-06-10 10:00:00', [[1, 3, 20.00]]);

        $rows = $this->report->clientsByPeriod(self::DESDE, self::HASTA, 2);

        $this->assertSame([], $rows);
    }
}

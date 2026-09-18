<?php

namespace Tests\Integration\Models;

use App\Models\Sale;
use App\Models\SaleReturn;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

/**
 * V8 — Indicador de devoluciones (FR-22): allWithDetails() expone
 *       tiene_devoluciones = 1 solo para ventas devueltas.
 *       Fechas literales en SQLite.
 */
final class SaleReturnsIndicatorTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Sale $sale;
    private SaleReturn $returns;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->sale = new Sale();
        $this->returns = new SaleReturn();
        $this->seedDependencies();
    }

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('Vendedor', 'vendedor@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
                          VALUES ('Cliente A', '12345678', '70000001', 'a@test.com'),
                                 ('Cliente B', '87654321', '70000002', 'b@test.com')");
        $this->pdo->exec("INSERT INTO tb_almacen
                          (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo,
                           stock_maximo, fecha_ingreso, id_usuario, id_categoria)
                          VALUES ('P001', 'Producto', 5.00, 20.00, 50, 1, 100, '2025-01-01', 1, 1)");
    }

    private function seedVenta(int $nroVenta, int $idCliente, float $total, string $fecha): int
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
                          VALUES ({$nroVenta}, {$idCliente}, 1, {$total}, '{$fecha}')");
        return (int)$this->pdo->lastInsertId();
    }

    private function seedDevolucion(int $idVenta, float $monto, string $fecha): void
    {
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto, fyh_creacion)
                          VALUES (0, {$idVenta}, 1, 'Devolución', {$monto}, '{$fecha}')");
        $idDevolucion = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("UPDATE tb_devoluciones SET nro_devolucion = {$idDevolucion} WHERE id_devolucion = {$idDevolucion}");
    }

    // ── V8 — Indicador (FR-22) ──────────────────────────────────────────────

    public function test_allWithDetails_shows_return_indicator_only_for_returned_sales(): void
    {
        $idVenta1 = $this->seedVenta(1, 1, 200.00, '2025-06-10 10:00:00');
        $idVenta2 = $this->seedVenta(2, 2, 150.00, '2025-06-12 11:00:00');
        $idVenta3 = $this->seedVenta(3, 1, 100.00, '2025-06-15 14:00:00');

        // Solo la venta 2 tiene devolución
        $this->seedDevolucion($idVenta2, 50.00, '2025-06-20 09:00:00');

        $rows = $this->sale->allWithDetails();

        $this->assertCount(3, $rows);

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int)$row['id_venta']] = $row;
        }

        // Venta 1 y 3 sin devoluciones
        $this->assertSame('0', (string)$byId[$idVenta1]['tiene_devoluciones']);
        $this->assertSame('0', (string)$byId[$idVenta3]['tiene_devoluciones']);

        // Venta 2 con devolución
        $this->assertSame('1', (string)$byId[$idVenta2]['tiene_devoluciones']);
    }

    public function test_allWithDetails_indicator_zero_when_no_returns_in_system(): void
    {
        $this->seedVenta(1, 1, 200.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 2, 150.00, '2025-06-12 11:00:00');

        $rows = $this->sale->allWithDetails();

        foreach ($rows as $row) {
            $this->assertSame('0', (string)$row['tiene_devoluciones']);
        }
    }

    public function test_allWithDetails_total_pagado_unchanged_despite_return(): void
    {
        $idVenta = $this->seedVenta(1, 1, 200.00, '2025-06-10 10:00:00');
        $this->seedDevolucion($idVenta, 80.00, '2025-06-20 09:00:00');

        $rows = $this->sale->allWithDetails();
        $venta = reset($rows);

        // total_pagado sigue siendo el original (FR-4)
        $this->assertEqualsWithDelta(200.00, (float)$venta['total_pagado'], 0.01);
        $this->assertSame('1', (string)$venta['tiene_devoluciones']);
    }

    public function test_allWithDetails_multiple_returns_on_same_sale_still_shows_one_indicator(): void
    {
        $idVenta = $this->seedVenta(1, 1, 200.00, '2025-06-10 10:00:00');
        $this->seedDevolucion($idVenta, 30.00, '2025-06-20 09:00:00');
        $this->seedDevolucion($idVenta, 50.00, '2025-06-25 10:00:00');

        $rows = $this->sale->allWithDetails();

        $this->assertCount(1, $rows);
        $this->assertSame('1', (string)$rows[0]['tiene_devoluciones']);
    }

    public function test_allWithDetails_user_scoping_filters_correctly(): void
    {
        // Crear un segundo usuario
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('Vendedor2', 'v2@test.com', 'hash', 1)");
        $idVenta1 = $this->seedVenta(1, 1, 200.00, '2025-06-10 10:00:00');
        // Venta del usuario 2
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
                          VALUES (2, 1, 2, 150.00, '2025-06-12 11:00:00')");
        $idVenta2 = (int)$this->pdo->lastInsertId();

        $this->seedDevolucion($idVenta1, 30.00, '2025-06-20 09:00:00');
        $this->seedDevolucion($idVenta2, 20.00, '2025-06-21 09:00:00');

        // Usuario 1 solo ve sus ventas
        $rows = $this->sale->allWithDetails(1);
        $this->assertCount(1, $rows);
        $this->assertSame('1', (string)$rows[0]['tiene_devoluciones']);

        // Usuario 2 solo ve sus ventas
        $rows = $this->sale->allWithDetails(2);
        $this->assertCount(1, $rows);
        $this->assertSame('1', (string)$rows[0]['tiene_devoluciones']);
    }
}

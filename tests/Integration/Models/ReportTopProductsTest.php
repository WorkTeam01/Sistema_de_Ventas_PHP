<?php

namespace Tests\Integration\Models;

use App\Models\Report;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class ReportTopProductsTest extends TestCase
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
                          VALUES ('Cliente A', '11111111', '70000001', 'a@test.com')");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria)
                          VALUES ('Electrónica'), ('Ropa')");
        $this->pdo->exec("INSERT INTO tb_almacen
                          (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
                          VALUES
                          ('P001', 'Celular', 100, 500.00, 800.00, '2025-01-01', 1, 1),
                          ('P002', 'Camisa',  100,  50.00, 100.00, '2025-01-01', 1, 2),
                          ('P003', 'Tablet',  100, 300.00, 500.00, '2025-01-01', 1, 1)");
    }

    private function seedVentaConItems(int $nroVenta, string $fecha, array $items): void
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, total_pagado, fyh_creacion)
                          VALUES ({$nroVenta}, 1, 0, '{$fecha}')");
        foreach ($items as [$idProducto, $cantidad]) {
            $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad)
                              VALUES ({$nroVenta}, {$idProducto}, {$cantidad})");
        }
    }

    // ── topProducts ───────────────────────────────────────────────────────────

    public function test_topProducts_returns_products_ordered_by_cantidad_desc(): void
    {
        // Celular: 3 unidades, Camisa: 5 unidades, Tablet: 1 unidad
        $this->seedVentaConItems(1, '2025-06-10 10:00:00', [[1, 3], [2, 5], [3, 1]]);

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59', 10, 'cantidad');

        $this->assertSame('Camisa', $rows[0]['nombre']);
        $this->assertSame(5, (int)$rows[0]['unidades_vendidas']);
        $this->assertSame('Celular', $rows[1]['nombre']);
    }

    public function test_topProducts_returns_products_ordered_by_ingresos_desc(): void
    {
        // Celular: 2 uds × Bs.800 = 1600, Camisa: 10 uds × Bs.100 = 1000
        $this->seedVentaConItems(1, '2025-06-10 10:00:00', [[1, 2], [2, 10]]);

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59', 10, 'ingresos');

        $this->assertSame('Celular', $rows[0]['nombre']);
        $this->assertEqualsWithDelta(1600.00, (float)$rows[0]['ingresos'], 0.01);
    }

    public function test_topProducts_respects_limit(): void
    {
        // top=5 está en la whitelist; con solo 3 productos con ventas devuelve 3
        $this->seedVentaConItems(1, '2025-06-10 10:00:00', [[1, 3], [2, 5], [3, 1]]);

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59', 5);

        $this->assertCount(3, $rows);
    }

    public function test_topProducts_filters_by_categoria(): void
    {
        $this->seedVentaConItems(1, '2025-06-10 10:00:00', [[1, 3], [2, 5], [3, 1]]);

        // id_categoria=2 → solo Camisa
        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59', 10, 'cantidad', 2);

        $this->assertCount(1, $rows);
        $this->assertSame('Camisa', $rows[0]['nombre']);
    }

    public function test_topProducts_excludes_sales_outside_range(): void
    {
        $this->seedVentaConItems(1, '2025-05-01 10:00:00', [[1, 10]]); // fuera de rango

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(0, $rows);
    }

    public function test_topProducts_product_without_sales_not_included(): void
    {
        // Solo Celular tiene ventas
        $this->seedVentaConItems(1, '2025-06-10 10:00:00', [[1, 1]]);

        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertSame('Celular', $rows[0]['nombre']);
    }

    public function test_topProducts_out_of_whitelist_top_defaults_to_10(): void
    {
        // Sembrar 15 productos con ventas
        for ($i = 4; $i <= 15; $i++) {
            $code = str_pad($i, 3, '0', STR_PAD_LEFT);
            $this->pdo->exec("INSERT INTO tb_almacen
                              (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
                              VALUES ('P{$code}', 'Producto {$i}', 10, 10.00, 20.00, '2025-01-01', 1, 1)");
        }
        $items = array_map(fn($id) => [$id, 1], range(1, 15));
        $this->seedVentaConItems(1, '2025-06-10 10:00:00', $items);

        // top=99 no está en whitelist → debe usar 10
        $rows = $this->report->topProducts('2025-06-01 00:00:00', '2025-06-30 23:59:59', 99);

        $this->assertCount(10, $rows);
    }
}

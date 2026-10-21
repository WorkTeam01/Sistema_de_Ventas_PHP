<?php

namespace Tests\Integration\Models;

use App\Models\InventoryReport;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class InventoryReportTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_proveedores
            (nombre_proveedor, celular, empresa, direccion)
            VALUES ('Proveedor Test', '90000000', 'Empresa Test', 'Dirección test')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P-00001', 'Producto Test', 10, 5.00, 10.00, '2026-01-01', 1, 1)");
    }

    public function test_summary_calculates_period_opening_balance_and_movements(): void
    {
        $this->pdo->exec("INSERT INTO tb_ajustes_stock
            (id_producto, tipo, cantidad, stock_anterior, stock_posterior, motivo, id_usuario, usuario_nombre, fyh_creacion)
            VALUES
            (1, 'entrada', 5, 10, 15, 'Entrada', 1, 'Admin', '2026-02-05 10:00:00'),
            (1, 'salida', 2, 15, 13, 'Salida', 1, 'Admin', '2026-02-10 10:00:00')");
        $this->pdo->exec("INSERT INTO tb_compras
            (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
            VALUES (1, 1, '2026-02-15', 1, 'Factura proveedor', 1, 5.00, 4)");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado, fyh_creacion)
            VALUES (1, 1, 80.00, '2026-02-20 10:00:00')");
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario)
            VALUES (1, 1, 8, 10.00)");
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto, fyh_creacion)
            VALUES (1, 1, 1, 'Reintegro', 10.00, '2026-02-21 10:00:00')");
        $this->pdo->exec("INSERT INTO tb_devolucion_items (id_devolucion, id_producto, cantidad, precio_unitario)
            VALUES (1, 1, 1, 10.00)");
        $this->pdo->exec("INSERT INTO tb_ajustes_stock
            (id_producto, tipo, cantidad, stock_anterior, stock_posterior, motivo, id_usuario, usuario_nombre, fyh_creacion)
            VALUES (1, 'entrada', 3, 10, 13, 'Entrada posterior', 1, 'Admin', '2026-04-01 10:00:00')");
        $this->pdo->exec("UPDATE tb_almacen SET stock = 13 WHERE id_producto = 1");
        $movement = $this->pdo->prepare(
            'INSERT INTO tb_movimientos_inventario
                (id_producto, codigo_producto, nombre_producto, unidad_medida, fecha,
                 cantidad, tipo, referencia, id_usuario, usuario_nombre)
             VALUES (1, \'P-00001\', \'Producto Test\', \'unidad\', ?, ?, ?, ?, 1, \'Admin\')'
        );
        foreach ([
            ['2026-02-05 10:00:00', 5, 'Ajuste de entrada', 'Entrada'],
            ['2026-02-10 10:00:00', -2, 'Ajuste de salida', 'Salida'],
            ['2026-02-15 00:00:00', 4, 'Compra', 'Factura proveedor'],
            ['2026-02-20 10:00:00', -8, 'Venta', '1'],
            ['2026-02-21 10:00:00', 1, 'Devolución', '1'],
            ['2026-04-01 10:00:00', 3, 'Ajuste de entrada', 'Entrada posterior'],
        ] as $row) {
            $movement->execute($row);
        }

        $report = new InventoryReport();
        $summary = $report->summary(['desde' => '2026-02-01', 'hasta' => '2026-02-28']);
        $movements = $report->movements(['desde' => '2026-02-01', 'hasta' => '2026-02-28']);

        $this->assertCount(1, $summary);
        $this->assertSame(10, (int)$summary[0]['stock_inicial']);
        $this->assertSame(10, (int)$summary[0]['ingresos']);
        $this->assertSame(10, (int)$summary[0]['salidas']);
        $this->assertSame(13, (int)$summary[0]['stock_actual']);
        $this->assertCount(5, $movements);
    }
}

<?php

namespace Tests\Integration\Models;

use App\Models\Sale;
use App\Models\SaleReturn;
use PDO;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class SaleReturnTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private SaleReturn $returns;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->returns = new SaleReturn();

        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
             VALUES ('Vendedor', 'vendedor@test.com', 'hash', 1)"
        );
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec(
            "INSERT INTO tb_clientes
                (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
             VALUES ('Cliente', '123', '70000000', 'cliente@test.com')"
        );
        $this->pdo->exec(
            "INSERT INTO tb_almacen
                (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo,
                 stock_maximo, fecha_ingreso, id_usuario, id_categoria)
             VALUES ('P001', 'Producto', 5.00, 12.50, 7, 1, 20,
                     '2026-01-01', 1, 1)"
        );
        $this->pdo->exec(
            "INSERT INTO tb_carrito
                (nro_venta, id_producto, cantidad, precio_unitario)
             VALUES (10, 1, 5, 12.50)"
        );
        $this->pdo->exec(
            "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado)
             VALUES (10, 1, 1, 62.50)"
        );
    }

    public function test_register_reingresa_stock_calcula_monto_y_acumula_pendiente(): void
    {
        $result = $this->returns->register(1, 'Cliente devolvió unidades', [1 => 2]);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['id']);
        $this->assertSame(1, $result['nro']);
        $this->assertEqualsWithDelta(25.00, $result['monto'], 0.001);
        $this->assertSame(9, (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn());

        $pending = $this->returns->pendingByVenta(1);
        $this->assertSame(3, (int)$pending[0]['pendiente']);

        $second = $this->returns->register(1, 'Devolución restante', [1 => 3]);
        $this->assertTrue($second['ok']);
        $this->assertSame(0, (int)$this->returns->pendingByVenta(1)[0]['pendiente']);
        $third = $this->returns->register(1, 'Excede el pendiente', [1 => 1]);
        $this->assertFalse($third['ok']);
        $this->assertSame('conflict', $third['error']);
        $this->assertSame(2, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_activity_log
             WHERE entidad = "sale_return" AND accion = "create"'
        )->fetchColumn());
        $this->assertSame(12, (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn());
    }

    public function test_register_rejects_conflict_without_changing_stock_or_rows(): void
    {
        $result = $this->returns->register(1, 'Cantidad inválida', [1 => 6]);

        $this->assertFalse($result['ok']);
        $this->assertSame('conflict', $result['error']);
        $this->assertSame(7, (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
    }

    public function test_register_rejects_empty_and_invalid_requests_without_side_effects(): void
    {
        $empty = $this->returns->register(1, 'Sin cantidades', [1 => 0]);
        $this->assertFalse($empty['ok']);
        $this->assertSame('empty', $empty['error']);

        $invalid = $this->returns->register(1, 'Cantidad inválida', [1 => -1]);
        $this->assertFalse($invalid['ok']);
        $this->assertSame('validation', $invalid['error']);

        $this->assertSame(7, (int)$this->pdo->query(
            'SELECT stock FROM tb_almacen WHERE id_producto = 1'
        )->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
    }

    public function test_register_rejects_missing_reason_and_sale(): void
    {
        $missingReason = $this->returns->register(1, '   ', [1 => 1]);
        $this->assertFalse($missingReason['ok']);
        $this->assertSame('validation', $missingReason['error']);

        $missingSale = $this->returns->register(999, 'Venta inexistente', [1 => 1]);
        $this->assertFalse($missingSale['ok']);
        $this->assertSame('not_found', $missingSale['error']);
        $this->assertSame(0, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_devoluciones'
        )->fetchColumn());
    }

    public function test_register_does_not_modify_original_sale_or_items(): void
    {
        $sale = new Sale();
        $before = $sale->findWithDetails(1);

        $result = $this->returns->register(1, 'Producto no requerido', [1 => 1]);
        $this->assertTrue($result['ok']);

        $after = $sale->findWithDetails(1);
        $this->assertSame($before['total_pagado'], $after['total_pagado']);
        $this->assertSame($before['nro_venta'], $after['nro_venta']);
        $this->assertCount(1, $after['items']);
        $this->assertSame($before['items'][0]['cantidad'], $after['items'][0]['cantidad']);
        $this->assertSame(
            $before['items'][0]['precio_unitario'],
            $after['items'][0]['precio_unitario']
        );
        $this->assertEqualsWithDelta(12.50, (float)$after['items'][0]['precio_venta'], 0.001);
    }

    public function test_register_records_return_activity_with_amount_and_details(): void
    {
        $result = $this->returns->register(1, 'Producto defectuoso', [1 => 2]);
        $this->assertTrue($result['ok']);

        $log = $this->pdo->query(
            "SELECT entidad, entidad_id, datos_nuevos
             FROM tb_activity_log
             WHERE entidad = 'sale_return'"
        )->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('sale_return', $log['entidad']);
        $this->assertSame($result['id'], (int)$log['entidad_id']);

        $data = json_decode($log['datos_nuevos'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(10, $data['nro_venta']);
        $this->assertSame('Producto defectuoso', $data['motivo']);
        $this->assertEqualsWithDelta(25.00, $data['monto_devuelto'], 0.001);
        $this->assertSame(1, $data['detalle'][0]['id_producto']);
        $this->assertSame(2, $data['detalle'][0]['cantidad']);
        $this->assertSame(12.50, $data['detalle'][0]['precio_unitario']);
    }

    public function test_sale_is_referenced_after_registering_return(): void
    {
        $sale = new Sale();

        $this->assertFalse($sale->isReferenced(1));

        $this->returns->register(1, 'Producto no requerido', [1 => 1]);

        $this->assertTrue($sale->isReferenced(1));
    }
}

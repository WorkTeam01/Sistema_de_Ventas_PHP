<?php

namespace Tests\Integration\Models;

use App\Models\PaymentMethod;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class PaymentMethodRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private PaymentMethod $methods;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->methods = new PaymentMethod();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function seedVenta(): int
    {
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
             VALUES ('P001', 'Producto', 5.00, 12.50, 7, 1, 20, '2026-01-01', 1, 1)"
        );
        $this->pdo->exec(
            "INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario)
             VALUES (10, 1, 5, 12.50)"
        );
        $this->pdo->exec(
            "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado)
             VALUES (10, 1, 1, 62.50)"
        );
        return 1;
    }

    private function createMethod(
        string $name = 'Efectivo',
        string $type = 'efectivo',
        int $active = 1
    ): int {
        $this->pdo->exec(
            "INSERT INTO tb_metodos_pago (nombre, tipo, activo)
             VALUES ('$name', '$type', $active)"
        );
        return (int)$this->pdo->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // create / nombre único / duplicado
    // -------------------------------------------------------------------------

    public function test_create_persists_method_with_unique_name(): void
    {
        $id = $this->methods->create([
            'nombre' => 'Efectivo',
            'tipo'   => 'efectivo',
            'activo' => 1,
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $row = $this->methods->find($id);
        $this->assertIsArray($row);
        $this->assertSame('Efectivo', $row['nombre']);
        $this->assertSame('efectivo', $row['tipo']);
        $this->assertSame(1, (int)$row['activo']);
    }

    public function test_nameExists_returns_true_for_duplicate_name(): void
    {
        $this->createMethod('Tarjeta');

        $this->assertTrue($this->methods->nameExists('Tarjeta'));
        $this->assertFalse($this->methods->nameExists('QR'));
    }

    public function test_create_duplicate_name_is_rejected_by_unique_constraint(): void
    {
        $this->createMethod('Efectivo');

        $this->expectException(\PDOException::class);
        $this->methods->create([
            'nombre' => 'Efectivo',
            'tipo'   => 'no_efectivo',
            'activo' => 1,
        ]);
    }

    public function test_nameExists_excludes_own_id_when_editing(): void
    {
        $id = $this->createMethod('Efectivo');

        $this->assertFalse($this->methods->nameExists('Efectivo', $id));
        $this->assertTrue($this->methods->nameExists('Efectivo', 999));
    }

    // -------------------------------------------------------------------------
    // active() — filtrado por estado
    // -------------------------------------------------------------------------

    public function test_active_returns_only_enabled_methods(): void
    {
        $this->createMethod('Efectivo', 'efectivo', 1);
        $this->createMethod('Tarjeta', 'no_efectivo', 0);
        $this->createMethod('QR', 'no_efectivo', 1);

        $active = $this->methods->active();

        $this->assertCount(2, $active);
        $names = array_column($active, 'nombre');
        $this->assertContains('Efectivo', $names);
        $this->assertContains('QR', $names);
        $this->assertNotContains('Tarjeta', $names);
    }

    public function test_deactivating_method_hides_it_from_active(): void
    {
        $id = $this->createMethod('Efectivo', 'efectivo', 1);
        $this->assertCount(1, $this->methods->active());

        $this->methods->update($id, ['activo' => 0]);

        $this->assertSame([], $this->methods->active());
        $row = $this->methods->find($id);
        $this->assertSame(0, (int)$row['activo']);
    }

    public function test_active_returns_empty_array_when_catalog_is_empty(): void
    {
        $this->assertSame([], $this->methods->active());
    }

    // -------------------------------------------------------------------------
    // isReferenced — bloqueo de eliminación (FR-2)
    // -------------------------------------------------------------------------

    public function test_isReferenced_is_false_when_method_has_no_payments(): void
    {
        $id = $this->createMethod('Efectivo');

        $this->assertFalse($this->methods->isReferenced($id));
    }

    public function test_isReferenced_is_true_when_method_has_at_least_one_payment(): void
    {
        $ventaId = $this->seedVenta();
        $metodoId = $this->createMethod('Efectivo', 'efectivo', 1);

        $this->assertFalse($this->methods->isReferenced($metodoId));

        $this->pdo->exec(
            "INSERT INTO tb_pagos (id_venta, id_metodo_pago, monto)
             VALUES ($ventaId, $metodoId, 62.50)"
        );

        $this->assertTrue($this->methods->isReferenced($metodoId));
    }

    public function test_isReferenced_is_false_for_unrelated_method_with_payments_present(): void
    {
        $ventaId = $this->seedVenta();
        $usedId = $this->createMethod('Efectivo', 'efectivo', 1);
        $otherId = $this->createMethod('Tarjeta', 'no_efectivo', 1);

        $this->pdo->exec(
            "INSERT INTO tb_pagos (id_venta, id_metodo_pago, monto)
             VALUES ($ventaId, $usedId, 62.50)"
        );

        $this->assertTrue($this->methods->isReferenced($usedId));
        $this->assertFalse($this->methods->isReferenced($otherId));
    }
}

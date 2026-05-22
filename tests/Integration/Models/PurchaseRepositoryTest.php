<?php

namespace Tests\Integration\Models;

use App\Models\Purchase;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class PurchaseRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Purchase $purchase;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->purchase = new Purchase();
        $this->seedDependencies();
    }

    // -------------------------------------------------------------------------
    // Helpers de seed
    // -------------------------------------------------------------------------

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_proveedores (nombre_proveedor, celular, empresa, direccion) VALUES ('Proveedor A', '70000001', 'Empresa A', 'Calle 1')");
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria) VALUES ('P001', 'Producto A', 10.00, 15.00, 50, 5, 100, '2026-01-01', 1, 1)");
    }

    private function storePurchase(int $cantidad = 10, int $productId = 1): bool
    {
        return $this->purchase->storeWithStock([
            'id_producto'   => $productId,
            'nro_compra'    => $this->purchase->nextNumber(),
            'fecha_compra'  => '2026-01-15',
            'id_proveedor'  => 1,
            'comprobante'   => 'FAC-001',
            'id_usuario'    => 1,
            'precio_compra' => 10.00,
            'cantidad'      => $cantidad,
        ]);
    }

    // -------------------------------------------------------------------------
    // nextNumber
    // -------------------------------------------------------------------------

    public function test_nextNumber_returns_1_when_table_is_empty(): void
    {
        $this->assertSame(1, $this->purchase->nextNumber());
    }

    public function test_nextNumber_increments_after_insert(): void
    {
        $this->storePurchase();

        $this->assertSame(2, $this->purchase->nextNumber());
    }

    // -------------------------------------------------------------------------
    // storeWithStock
    // -------------------------------------------------------------------------

    public function test_storeWithStock_inserts_purchase_record(): void
    {
        $result = $this->storePurchase(10);

        $this->assertTrue($result);
        $this->assertSame(1, $this->purchase->count());
    }

    public function test_storeWithStock_increments_product_stock(): void
    {
        $this->storePurchase(10);

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = 1")->fetch();
        $this->assertSame(60, (int)$row['stock']);
    }

    // -------------------------------------------------------------------------
    // updateWithStock — mismo producto
    // -------------------------------------------------------------------------

    public function test_updateWithStock_same_product_applies_net_diff(): void
    {
        $this->storePurchase(10);

        $this->purchase->updateWithStock([
            'id_compra'     => 1,
            'id_producto'   => 1,
            'nro_compra'    => 1,
            'fecha_compra'  => '2026-01-15',
            'id_proveedor'  => 1,
            'comprobante'   => 'FAC-001',
            'id_usuario'    => 1,
            'precio_compra' => 10.00,
            'cantidad'      => 20,
        ], 1, 10);

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = 1")->fetch();
        // Stock inicial 50 + 10 (store) + diff(20-10) = 70
        $this->assertSame(70, (int)$row['stock']);
    }

    // -------------------------------------------------------------------------
    // updateWithStock — producto distinto
    // -------------------------------------------------------------------------

    public function test_updateWithStock_different_product_reverts_old_and_adds_new(): void
    {
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria) VALUES ('P002', 'Producto B', 8.00, 12.00, 30, 2, 50, '2026-01-01', 1, 1)");

        $this->storePurchase(10, 1);

        $this->purchase->updateWithStock([
            'id_compra'     => 1,
            'id_producto'   => 2,
            'nro_compra'    => 1,
            'fecha_compra'  => '2026-01-15',
            'id_proveedor'  => 1,
            'comprobante'   => 'FAC-001',
            'id_usuario'    => 1,
            'precio_compra' => 8.00,
            'cantidad'      => 5,
        ], 1, 10);

        $rowA = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = 1")->fetch();
        $rowB = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = 2")->fetch();

        // Producto A: 50 + 10 (store) - 10 (revert) = 50
        $this->assertSame(50, (int)$rowA['stock']);
        // Producto B: 30 + 5 = 35
        $this->assertSame(35, (int)$rowB['stock']);
    }

    // -------------------------------------------------------------------------
    // destroyWithStock
    // -------------------------------------------------------------------------

    public function test_destroyWithStock_removes_purchase_and_reverts_stock(): void
    {
        $this->storePurchase(10);

        $result = $this->purchase->destroyWithStock(1, 1, 10);

        $this->assertTrue($result);
        $this->assertSame(0, $this->purchase->count());

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = 1")->fetch();
        $this->assertSame(50, (int)$row['stock']);
    }

    // -------------------------------------------------------------------------
    // allWithDetails / findWithDetails
    // -------------------------------------------------------------------------

    public function test_allWithDetails_returns_empty_array_on_empty_table(): void
    {
        $this->assertSame([], $this->purchase->allWithDetails());
    }

    public function test_allWithDetails_returns_joined_data(): void
    {
        $this->storePurchase();

        $rows = $this->purchase->allWithDetails();
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('nombre_producto', $rows[0]);
        $this->assertArrayHasKey('nombre_proveedor', $rows[0]);
        $this->assertSame('Producto A', $rows[0]['nombre_producto']);
    }

    public function test_findWithDetails_returns_null_for_nonexistent_id(): void
    {
        $this->assertNull($this->purchase->findWithDetails(999));
    }

    public function test_findWithDetails_returns_full_row(): void
    {
        $this->storePurchase();

        $row = $this->purchase->findWithDetails(1);
        $this->assertIsArray($row);
        $this->assertSame('Producto A', $row['nombre_producto']);
        $this->assertSame('Proveedor A', $row['nombre_proveedor']);
    }

    // -------------------------------------------------------------------------
    // validateData
    // -------------------------------------------------------------------------

    public function test_validateData_returns_true_for_valid_data(): void
    {
        $data = [
            'id_producto'   => 1,
            'id_proveedor'  => 1,
            'nro_compra'    => 1,
            'fecha_compra'  => '2026-01-15',
            'comprobante'   => 'FAC-001',
            'precio_compra' => 10.00,
            'cantidad'      => 5,
        ];

        $this->assertTrue($this->purchase->validateData($data));
    }

    public function test_validateData_returns_errors_for_missing_fields(): void
    {
        $result = $this->purchase->validateData([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id_producto', $result);
        $this->assertArrayHasKey('id_proveedor', $result);
        $this->assertArrayHasKey('fecha_compra', $result);
        $this->assertArrayHasKey('comprobante', $result);
        $this->assertArrayHasKey('precio_compra', $result);
        $this->assertArrayHasKey('cantidad', $result);
    }

    public function test_validateData_rejects_zero_price(): void
    {
        $data = [
            'id_producto'   => 1,
            'id_proveedor'  => 1,
            'nro_compra'    => 1,
            'fecha_compra'  => '2026-01-15',
            'comprobante'   => 'FAC-001',
            'precio_compra' => 0,
            'cantidad'      => 5,
        ];

        $result = $this->purchase->validateData($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('precio_compra', $result);
    }

    public function test_validateData_rejects_zero_quantity(): void
    {
        $data = [
            'id_producto'   => 1,
            'id_proveedor'  => 1,
            'nro_compra'    => 1,
            'fecha_compra'  => '2026-01-15',
            'comprobante'   => 'FAC-001',
            'precio_compra' => 10.00,
            'cantidad'      => 0,
        ];

        $result = $this->purchase->validateData($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cantidad', $result);
    }

    public function test_validateData_rejects_short_comprobante(): void
    {
        $data = [
            'id_producto'   => 1,
            'id_proveedor'  => 1,
            'nro_compra'    => 1,
            'fecha_compra'  => '2026-01-15',
            'comprobante'   => 'AB',
            'precio_compra' => 10.00,
            'cantidad'      => 5,
        ];

        $result = $this->purchase->validateData($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('comprobante', $result);
    }
}

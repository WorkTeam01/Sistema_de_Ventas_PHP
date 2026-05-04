<?php

namespace Tests\Integration\Models;

use App\Models\Supplier;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class SupplierRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Supplier $supplier;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->supplier = new Supplier();
    }

    // -------------------------------------------------------------------------
    // Helpers de seed mínimos
    // -------------------------------------------------------------------------

    private function createSupplier(string $empresa = 'Empresa SA'): int
    {
        $this->supplier->createSupplier(
            'Proveedor Test',
            $empresa,
            '70000000',
            'Calle 1',
            null,
            null
        );
        return (int)$this->pdo->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // createSupplier
    // -------------------------------------------------------------------------

    public function test_createSupplier_persists_record(): void
    {
        $id = $this->createSupplier();

        $row = $this->supplier->find($id);
        $this->assertIsArray($row);
        $this->assertSame('Proveedor Test', $row['nombre_proveedor']);
        $this->assertSame('Empresa SA', $row['empresa']);
        $this->assertSame('70000000', $row['celular']);
    }

    public function test_createSupplier_stores_null_for_empty_telefono(): void
    {
        $this->supplier->createSupplier('P', 'E', '70000000', 'Dir', '', null);

        $row = $this->supplier->find(1);
        $this->assertNull($row['telefono']);
    }

    public function test_createSupplier_stores_null_for_empty_email(): void
    {
        $this->supplier->createSupplier('P', 'E', '70000000', 'Dir', null, '');

        $row = $this->supplier->find(1);
        $this->assertNull($row['email']);
    }

    public function test_createSupplier_stores_provided_telefono_and_email(): void
    {
        $this->supplier->createSupplier('P', 'E', '70000000', 'Dir', '22223333', 'p@empresa.com');

        $row = $this->supplier->find(1);
        $this->assertSame('22223333', $row['telefono']);
        $this->assertSame('p@empresa.com', $row['email']);
    }

    // -------------------------------------------------------------------------
    // updateSupplier
    // -------------------------------------------------------------------------

    public function test_updateSupplier_modifies_row(): void
    {
        $id = $this->createSupplier();

        $result = $this->supplier->updateSupplier($id, 'Nuevo Proveedor', 'Nueva SA', '71111111', 'Av. 2', null, null);

        $this->assertTrue($result);
        $row = $this->supplier->find($id);
        $this->assertSame('Nuevo Proveedor', $row['nombre_proveedor']);
        $this->assertSame('Nueva SA', $row['empresa']);
    }

    public function test_updateSupplier_normalizes_empty_to_null(): void
    {
        $id = $this->createSupplier();

        $this->supplier->updateSupplier($id, 'P', 'E', '70000000', 'Dir', '', '');

        $row = $this->supplier->find($id);
        $this->assertNull($row['telefono']);
        $this->assertNull($row['email']);
    }

    // -------------------------------------------------------------------------
    // isReferenced
    // -------------------------------------------------------------------------

    public function test_isReferenced_false_when_no_purchases(): void
    {
        $id = $this->createSupplier();
        $this->assertFalse($this->supplier->isReferenced($id));
    }

    public function test_isReferenced_true_when_supplier_has_purchases(): void
    {
        $id = $this->createSupplier();

        // Seedear dependencias mínimas para crear una compra
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P-001', 'Producto', 10, 5.00, 10.00, '2026-01-01', 1, 1)");
        $this->pdo->exec("INSERT INTO tb_compras
            (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
            VALUES (1, 1, '2026-01-01', $id, 'COMP-001', 1, 5.00, 10)");

        $this->assertTrue($this->supplier->isReferenced($id));
    }

    // -------------------------------------------------------------------------
    // nameExists
    // -------------------------------------------------------------------------

    public function test_nameExists_returns_true_for_duplicate(): void
    {
        $this->createSupplier('Empresa SA');
        $this->assertTrue($this->supplier->nameExists('Empresa SA'));
    }

    public function test_nameExists_returns_false_for_unique(): void
    {
        $this->assertFalse($this->supplier->nameExists('Empresa Nueva'));
    }

    public function test_nameExists_excludes_own_id_on_edit(): void
    {
        $id = $this->createSupplier('Empresa SA');
        $this->assertFalse($this->supplier->nameExists('Empresa SA', $id));
    }
}

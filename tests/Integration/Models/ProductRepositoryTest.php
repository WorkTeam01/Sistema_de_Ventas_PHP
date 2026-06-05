<?php

namespace Tests\Integration\Models;

use App\Models\Product;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Product $product;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->product = new Product();
        $this->seedDependencies();
    }

    // -------------------------------------------------------------------------
    // Helpers de seed mínimos
    // -------------------------------------------------------------------------

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Categoría Test')");
    }

    private function productData(array $overrides = []): array
    {
        return array_merge([
            'codigo'        => 'P-00001',
            'nombre'        => 'Producto Test',
            'descripcion'   => 'Descripción del producto',
            'stock'         => 50,
            'stock_minimo'  => 5,
            'stock_maximo'  => 100,
            'precio_compra' => 10.00,
            'precio_venta'  => 20.00,
            'fecha_ingreso' => '2026-01-01',
            'imagen'        => 'producto_default.png',
            'id_usuario'    => 1,
            'id_categoria'  => 1,
        ], $overrides);
    }

    private function createProduct(array $overrides = []): int
    {
        $this->product->createProduct($this->productData($overrides));
        return (int)$this->pdo->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // createProduct
    // -------------------------------------------------------------------------

    public function test_createProduct_persists_record(): void
    {
        $id = $this->createProduct();

        $row = $this->product->find($id);
        $this->assertIsArray($row);
        $this->assertSame('P-00001', $row['codigo']);
        $this->assertSame('Producto Test', $row['nombre']);
        $this->assertSame(50, (int)$row['stock']);
        $this->assertEqualsWithDelta(20.00, (float)$row['precio_venta'], 0.001);
    }

    public function test_createProduct_stores_null_for_empty_descripcion(): void
    {
        $this->createProduct(['descripcion' => '']);

        $row = $this->product->find(1);
        $this->assertNull($row['descripcion']);
    }

    public function test_createProduct_stores_null_for_empty_stock_minimo(): void
    {
        $this->createProduct(['stock_minimo' => '']);

        $row = $this->product->find(1);
        $this->assertNull($row['stock_minimo']);
    }

    public function test_createProduct_stores_null_for_empty_stock_maximo(): void
    {
        $this->createProduct(['stock_maximo' => null]);

        $row = $this->product->find(1);
        $this->assertNull($row['stock_maximo']);
    }

    // -------------------------------------------------------------------------
    // findWithCategory
    // -------------------------------------------------------------------------

    public function test_findWithCategory_returns_category_name(): void
    {
        $id = $this->createProduct();

        $row = $this->product->findWithCategory($id);
        $this->assertIsArray($row);
        $this->assertArrayHasKey('nombre_categoria', $row);
        $this->assertSame('Categoría Test', $row['nombre_categoria']);
    }

    public function test_findWithCategory_returns_false_for_nonexistent(): void
    {
        $this->assertFalse($this->product->findWithCategory(9999));
    }

    // -------------------------------------------------------------------------
    // allWithCategories
    // -------------------------------------------------------------------------

    public function test_allWithCategories_returns_join_data(): void
    {
        $this->createProduct(['codigo' => 'P-00001']);
        $this->createProduct(['codigo' => 'P-00002', 'nombre' => 'Producto 2']);

        $rows = $this->product->allWithCategories();
        $this->assertCount(2, $rows);
        $this->assertArrayHasKey('nombre_categoria', $rows[0]);
        $this->assertArrayHasKey('email_usuario', $rows[0]);
    }

    // -------------------------------------------------------------------------
    // nextCode
    // -------------------------------------------------------------------------

    public function test_nextCode_returns_p_00001_when_empty(): void
    {
        $this->assertSame('P-00001', $this->product->nextCode());
    }

    public function test_nextCode_increments_with_existing_records(): void
    {
        $this->createProduct(['codigo' => 'P-00001']);

        $this->assertSame('P-00002', $this->product->nextCode());
    }

    // -------------------------------------------------------------------------
    // isReferenced
    // -------------------------------------------------------------------------

    public function test_isReferenced_false_when_orphan(): void
    {
        $id = $this->createProduct();
        $this->assertFalse($this->product->isReferenced($id));
    }

    public function test_isReferenced_true_when_product_in_carrito(): void
    {
        $id = $this->createProduct();
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad)
            VALUES (1, $id, 2)");

        $this->assertTrue($this->product->isReferenced($id));
    }

    public function test_isReferenced_true_when_product_in_compras(): void
    {
        $id = $this->createProduct();
        $this->pdo->exec("INSERT INTO tb_proveedores
            (nombre_proveedor, celular, empresa, direccion) VALUES ('P', '123', 'E', 'Dir')");
        $this->pdo->exec("INSERT INTO tb_compras
            (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
            VALUES ($id, 1, '2026-01-01', 1, 'COMP-001', 1, 5.00, 10)");

        $this->assertTrue($this->product->isReferenced($id));
    }

    // -------------------------------------------------------------------------
    // getTopSelling
    // -------------------------------------------------------------------------

    private function seedSale(int $nroVenta, int $idCliente): void
    {
        $this->pdo->exec("INSERT INTO tb_clientes
            (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Cliente $idCliente', '$idCliente', '7000000$idCliente', 'c$idCliente@test.com')");
        $clientId = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, total_pagado)
            VALUES ($nroVenta, $clientId, 0)");
    }

    public function test_getTopSelling_ordena_por_cantidad_descendente(): void
    {
        $p1 = $this->createProduct(['codigo' => 'P-00001', 'nombre' => 'Producto A', 'precio_venta' => 10.00]);
        $p2 = $this->createProduct(['codigo' => 'P-00002', 'nombre' => 'Producto B', 'precio_venta' => 10.00]);

        $this->seedSale(1, 1);
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, $p1, 3)");
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, $p2, 7)");

        $result = $this->product->getTopSelling(5);

        $this->assertSame('Producto B', $result[0]['nombre']);
        $this->assertSame('Producto A', $result[1]['nombre']);
    }

    public function test_getTopSelling_respeta_limit(): void
    {
        $this->seedSale(1, 1);
        for ($i = 1; $i <= 4; $i++) {
            $pid = $this->createProduct(['codigo' => "P-0000$i", 'nombre' => "Prod $i", 'precio_venta' => 5.00]);
            $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, $pid, $i)");
        }

        $this->assertCount(2, $this->product->getTopSelling(2));
    }

    public function test_getTopSelling_ignora_carrito_sin_venta_confirmada(): void
    {
        $pid = $this->createProduct(['codigo' => 'P-00001', 'nombre' => 'Producto A', 'precio_venta' => 10.00]);
        // Carrito huérfano: nro_venta 99 no existe en tb_ventas
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (99, $pid, 5)");

        $result = $this->product->getTopSelling(5);

        $this->assertEmpty($result);
    }

    public function test_getTopSelling_calcula_ingresos(): void
    {
        $pid = $this->createProduct(['codigo' => 'P-00001', 'nombre' => 'Producto A', 'precio_venta' => 15.50]);
        $this->seedSale(1, 1);
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, $pid, 4)");

        $result = $this->product->getTopSelling(5);

        $this->assertCount(1, $result);
        $this->assertEqualsWithDelta(62.00, (float)$result[0]['ingresos'], 0.001);
        $this->assertSame(4, (int)$result[0]['cantidad_vendida']);
    }
}

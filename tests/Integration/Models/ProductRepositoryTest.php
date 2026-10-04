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
        return (int)$this->product->createProduct($this->productData($overrides));
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
    // Stock inicial y edición sin stock
    // -------------------------------------------------------------------------

    public function test_createProduct_registra_stock_inicial_como_entrada(): void
    {
        $id = $this->createProduct(['stock' => 5]);

        $this->assertSame(5, (int)$this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = $id")->fetchColumn());

        $rows = $this->pdo->query("SELECT * FROM tb_ajustes_stock WHERE id_producto = $id")->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertSame('entrada', $rows[0]['tipo']);
        $this->assertSame(5, (int)$rows[0]['cantidad']);
        $this->assertSame(0, (int)$rows[0]['stock_anterior']);
        $this->assertSame(5, (int)$rows[0]['stock_posterior']);
        $this->assertSame('Stock inicial', $rows[0]['motivo']);
    }

    public function test_createProduct_con_stock_cero_no_registra_ajuste(): void
    {
        $id = $this->createProduct(['stock' => 0]);

        $this->assertGreaterThan(0, $id);
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM tb_ajustes_stock")->fetchColumn());
    }

    public function test_createProduct_respeta_transaccion_externa_sin_anidar(): void
    {
        $this->pdo->beginTransaction();
        $id = $this->createProduct(['stock' => 3]);
        $this->assertTrue($this->pdo->inTransaction());
        $this->pdo->rollBack();

        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM tb_almacen WHERE id_producto = $id")->fetchColumn());
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM tb_ajustes_stock")->fetchColumn());
    }

    public function test_updateProduct_ignora_stock_e_id_usuario(): void
    {
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Otro', 'otro@test.com', 'hash', 1)");
        $id = $this->createProduct(['stock' => 7, 'id_usuario' => 1]);

        $this->assertTrue($this->product->updateProduct($id, $this->productData([
            'nombre'     => 'Renombrado',
            'stock'      => 999,
            'id_usuario' => 2,
        ])));

        $row = $this->product->find($id);
        $this->assertSame('Renombrado', $row['nombre']);
        $this->assertSame(7, (int)$row['stock']);
        $this->assertSame(1, (int)$row['id_usuario']);
    }

    // -------------------------------------------------------------------------
    // validateData (límites)
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('validationProvider')]
    public function test_validateData_limits(array $override, ?string $invalidField): void
    {
        $result = $this->product->validateData(array_merge(
            ['stock' => 10, 'precio_compra' => '5.00', 'precio_venta' => '8.00'],
            $override
        ));

        if ($invalidField === null) {
            $this->assertTrue($result);
        } else {
            $this->assertIsArray($result);
            $this->assertArrayHasKey($invalidField, $result);
        }
    }

    public static function validationProvider(): array
    {
        return [
            'datos válidos'           => [[], null],
            'precio venta mínimo'     => [['precio_venta' => '0.01'], null],
            'precio venta cero'       => [['precio_venta' => '0'], 'precio_venta'],
            'precio venta máximo'     => [['precio_venta' => '9999999.99'], null],
            'precio venta excedido'   => [['precio_venta' => '10000000'], 'precio_venta'],
            'precio compra cero'      => [['precio_compra' => 0], 'precio_compra'],
            'precio compra excedido'  => [['precio_compra' => '10000000.00'], 'precio_compra'],
            'precio no numérico'      => [['precio_compra' => 'abc'], 'precio_compra'],
            'stock cero'              => [['stock' => 0], null],
            'stock máximo'            => [['stock' => 1000000], null],
            'stock excedido'          => [['stock' => 1000001], 'stock'],
            'stock negativo'          => [['stock' => -1], 'stock'],
            'stock decimal'           => [['stock' => '2.5'], 'stock'],
        ];
    }

    public function test_validateData_skips_stock_when_absent(): void
    {
        $this->assertTrue($this->product->validateData(['precio_compra' => 5, 'precio_venta' => 8]));
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

    public function test_nextCode_skips_gaps_left_by_deleted_products(): void
    {
        $id1 = $this->createProduct(['codigo' => 'P-00001', 'stock' => 0]);
        $this->createProduct(['codigo' => 'P-00002']);

        $this->product->delete($id1);

        // count()+1 devolvería P-00002, ya ocupado, y el UNIQUE de codigo
        // rechazaría el alta con un error 500.
        $codigo = $this->product->nextCode();
        $this->assertSame('P-00003', $codigo);
        $this->assertNotFalse($this->createProduct(['codigo' => $codigo]));
    }

    // -------------------------------------------------------------------------
    // isReferenced
    // -------------------------------------------------------------------------

    public function test_isReferenced_false_when_orphan(): void
    {
        $id = $this->createProduct(['stock' => 0]);
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

    public function test_isReferenced_true_when_product_has_stock_adjustment(): void
    {
        $id = $this->createProduct(['stock' => 0]);
        $this->pdo->exec("INSERT INTO tb_ajustes_stock
            (id_producto, tipo, cantidad, stock_anterior, stock_posterior, motivo)
            VALUES ($id, 'entrada', 3, 0, 3, 'Ajuste')");

        $this->assertTrue($this->product->isReferenced($id));
        $this->assertSame(1, $this->product->getReferenceCount($id)['ajustes']);
    }

    public function test_isReferenced_true_when_product_has_return_line(): void
    {
        $id = $this->createProduct(['stock' => 0]);
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES (1, 1, 20.00)");
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, motivo, monto)
            VALUES (1, 1, 'Motivo', 20.00)");
        $this->pdo->exec("INSERT INTO tb_devolucion_items (id_devolucion, id_producto, cantidad, precio_unitario)
            VALUES (1, $id, 1, 20.00)");

        $this->assertTrue($this->product->isReferenced($id));
        $this->assertSame(1, $this->product->getReferenceCount($id)['devoluciones']);
    }

    public function test_product_with_stock_adjustment_cannot_be_deleted_by_guard(): void
    {
        $id = $this->createProduct(['stock' => 5]);

        $this->assertTrue($this->product->isReferenced($id));
        $this->assertSame(
            ['carrito' => 0, 'compras' => 0, 'ajustes' => 1, 'devoluciones' => 0],
            $this->product->getReferenceCount($id)
        );
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

    public function test_getTopSelling_subtracts_returned_quantities_and_historical_amounts_with_user_scope(): void
    {
        $pid = $this->createProduct([
            'codigo' => 'P-00001',
            'nombre' => 'Producto A',
            'precio_venta' => 99.00,
        ]);
        $this->seedSale(1, 1);
        $this->pdo->exec(
            "UPDATE tb_ventas SET id_usuario = 1, total_pagado = 40.00 WHERE id_venta = 1"
        );
        $this->pdo->exec(
            "INSERT INTO tb_carrito
                (nro_venta, id_producto, cantidad, precio_unitario)
             VALUES (1, $pid, 4, 10.00)"
        );
        $this->pdo->exec(
            "INSERT INTO tb_devoluciones
                (nro_devolucion, id_venta, id_usuario, motivo, monto, fyh_creacion)
             VALUES (1, 1, 1, 'Ajuste', 20.00, datetime('now'))"
        );
        $this->pdo->exec(
            "INSERT INTO tb_devolucion_items
                (id_devolucion, id_producto, cantidad, precio_unitario)
             VALUES (1, $pid, 2, 10.00)"
        );

        $result = $this->product->getTopSelling(5, 1);

        $this->assertCount(1, $result);
        $this->assertSame(2, (int)$result[0]['cantidad_vendida']);
        $this->assertEqualsWithDelta(20.00, (float)$result[0]['ingresos'], 0.001);
    }
}

<?php

namespace Tests\Integration\Models;

use App\Models\Product;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class InventoryListFieldsTest extends TestCase
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

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Categoría Test')");
        $this->pdo->exec(
            "INSERT INTO tb_almacen (codigo, nombre, stock, stock_minimo, stock_maximo,
             precio_compra, precio_venta, fecha_ingreso, imagen, id_usuario, id_categoria)
             VALUES ('P-00001', 'Producto Test', 5, 3, 10, 2.50, 5.00, '2026-01-01', 'producto_default.png', 1, 1)"
        );
    }

    public function test_inventoryList_retorna_campos_para_badge(): void
    {
        $rows = $this->product->inventoryList('todos');

        $this->assertNotEmpty($rows);
        $first = $rows[0];

        $this->assertArrayHasKey('stock', $first);
        $this->assertArrayHasKey('stock_minimo', $first);
        $this->assertArrayHasKey('stock_maximo', $first);
        $this->assertArrayHasKey('nombre_categoria', $first);

        $this->assertSame(5, (int)$first['stock']);
        $this->assertSame(3, (int)$first['stock_minimo']);
        $this->assertSame(10, (int)$first['stock_maximo']);
        $this->assertSame('Categoría Test', $first['nombre_categoria']);
    }

    public function test_inventoryStats_con_producto_existente(): void
    {
        $stats = $this->product->inventoryStats();

        $this->assertIsInt($stats['total']);
        $this->assertIsInt($stats['agotados']);
        $this->assertIsInt($stats['bajo_minimo']);
        $this->assertIsFloat($stats['valor_total']);

        $this->assertSame(1, $stats['total']);
        $this->assertSame(0, $stats['agotados']);   // stock=5, no agotado
        $this->assertSame(0, $stats['bajo_minimo']); // stock=5 > stock_minimo=3
        $this->assertEqualsWithDelta(12.50, $stats['valor_total'], 0.001); // 5 * 2.50
    }

    public function test_inventoryStats_tabla_vacia_retorna_ceros(): void
    {
        $this->pdo->exec("DELETE FROM tb_almacen");

        $stats = $this->product->inventoryStats();

        $this->assertSame(0, $stats['total']);
        $this->assertSame(0, $stats['agotados']);
        $this->assertSame(0, $stats['bajo_minimo']);
        $this->assertSame(0.0, $stats['valor_total']);
    }
}

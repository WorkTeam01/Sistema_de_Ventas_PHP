<?php

namespace Tests\Integration\Models;

use App\Models\StockAdjustment;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private StockAdjustment $adj;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->adj = new StockAdjustment();
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

    private function createProduct(array $overrides = []): int
    {
        $data = array_merge([
            'codigo'        => 'P-00001',
            'nombre'        => 'Producto Test',
            'stock'         => 50,
            'stock_minimo'  => 10,
            'stock_maximo'  => 100,
            'precio_compra' => 10.00,
            'precio_venta'  => 20.00,
            'fecha_ingreso' => '2026-01-01',
            'imagen'        => 'producto_default.png',
            'id_usuario'    => 1,
            'id_categoria'  => 1,
        ], $overrides);

        $this->pdo->prepare(
            "INSERT INTO tb_almacen (codigo, nombre, stock, stock_minimo, stock_maximo,
             precio_compra, precio_venta, fecha_ingreso, imagen, id_usuario, id_categoria)
             VALUES (:codigo, :nombre, :stock, :stock_minimo, :stock_maximo,
             :precio_compra, :precio_venta, :fecha_ingreso, :imagen, :id_usuario, :id_categoria)"
        )->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_register_entrada_incrementa_stock(): void
    {
        $id = $this->createProduct(['stock' => 20, 'codigo' => 'P-00001']);

        $result = $this->adj->register($id, 'entrada', 5, 'Motivo test');

        $this->assertTrue($result['ok']);

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = $id")->fetch();
        $this->assertSame(25, (int)$row['stock']);

        $log = $this->pdo->query("SELECT * FROM tb_ajustes_stock WHERE id_producto = $id")->fetch();
        $this->assertIsArray($log);
        $this->assertSame(20, (int)$log['stock_anterior']);
        $this->assertSame(25, (int)$log['stock_posterior']);
        $this->assertSame('entrada', $log['tipo']);
        $this->assertSame(5, (int)$log['cantidad']);
    }

    public function test_register_salida_decrementa_stock(): void
    {
        $id = $this->createProduct(['stock' => 20, 'codigo' => 'P-00001']);

        $result = $this->adj->register($id, 'salida', 8, 'Salida test');

        $this->assertTrue($result['ok']);

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = $id")->fetch();
        $this->assertSame(12, (int)$row['stock']);

        $log = $this->pdo->query("SELECT * FROM tb_ajustes_stock WHERE id_producto = $id")->fetch();
        $this->assertIsArray($log);
        $this->assertSame(20, (int)$log['stock_anterior']);
        $this->assertSame(12, (int)$log['stock_posterior']);
        $this->assertSame('salida', $log['tipo']);
        $this->assertSame(8, (int)$log['cantidad']);
    }

    public function test_register_salida_rechaza_si_stock_insuficiente(): void
    {
        $id = $this->createProduct(['stock' => 5, 'codigo' => 'P-00001']);

        $result = $this->adj->register($id, 'salida', 10, 'Salida excesiva');

        $this->assertFalse($result['ok']);

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = $id")->fetch();
        $this->assertSame(5, (int)$row['stock']);

        $count = $this->pdo->query("SELECT COUNT(*) FROM tb_ajustes_stock WHERE id_producto = $id")->fetchColumn();
        $this->assertSame(0, (int)$count);
    }

    public function test_register_retorna_error_si_producto_no_existe(): void
    {
        $result = $this->adj->register(9999, 'entrada', 5, 'Test producto inexistente');

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
        $this->assertNotEmpty($result['error']);
    }

    public function test_register_retorna_ok_y_ajuste_insertado(): void
    {
        $id = $this->createProduct(['stock' => 15, 'codigo' => 'P-00001']);

        $result = $this->adj->register($id, 'entrada', 3, 'Test activity log');

        // register() retorna ok=true aunque ActivityLog falle silenciosamente (fire-and-forget)
        $this->assertTrue($result['ok']);
        $this->assertSame(18, $result['stock_posterior']);

        // El ajuste en tb_ajustes_stock sí debe haberse insertado
        $count = $this->pdo->query("SELECT COUNT(*) FROM tb_ajustes_stock WHERE id_producto = $id")->fetchColumn();
        $this->assertSame(1, (int)$count);
    }

    public function test_history_filtra_por_tipo(): void
    {
        $id = $this->createProduct(['stock' => 50, 'codigo' => 'P-00001']);

        $this->adj->register($id, 'entrada', 10, 'Entrada test');
        $this->adj->register($id, 'salida', 5, 'Salida test');

        $entradas = $this->adj->history(['tipo' => 'entrada']);
        $salidas   = $this->adj->history(['tipo' => 'salida']);
        $todos     = $this->adj->history([]);

        $this->assertCount(1, $entradas);
        $this->assertSame('entrada', $entradas[0]['tipo']);

        $this->assertCount(1, $salidas);
        $this->assertSame('salida', $salidas[0]['tipo']);

        $this->assertCount(2, $todos);
    }
}

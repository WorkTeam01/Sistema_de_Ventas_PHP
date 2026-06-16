<?php

namespace Tests\Integration\Models;

use App\Models\Sale;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class SaleRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Sale $sale;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->sale = new Sale();
        $this->seedDependencies();
    }

    // -------------------------------------------------------------------------
    // Helpers de seed
    // -------------------------------------------------------------------------

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Vendedor', 'vendedor@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente) VALUES ('Cliente A', '12345678', '70000001', 'cliente@test.com')");
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria) VALUES ('P001', 'Producto A', 5.00, 10.00, 50, 2, 100, '2026-01-01', 1, 1)");
    }

    private function seedCart(int $nroVenta, int $productId = 1, int $cantidad = 3): void
    {
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES ($nroVenta, $productId, $cantidad)");
    }

    private function storeSale(int $nroVenta = 1, int $cantidad = 3): bool
    {
        $this->seedCart($nroVenta, 1, $cantidad);
        return $this->sale->storeWithStock([
            'nro_venta'    => $nroVenta,
            'id_cliente'   => 1,
            'total_pagado' => $cantidad * 10.00,
        ]);
    }

    // -------------------------------------------------------------------------
    // nextNumber
    // -------------------------------------------------------------------------

    public function test_nextNumber_returns_1_when_table_is_empty(): void
    {
        $this->assertSame(1, $this->sale->nextNumber());
    }

    public function test_nextNumber_uses_max_not_count_to_avoid_reuse(): void
    {
        $this->storeSale(1);
        $this->storeSale(5); // nro_venta no secuencial para verificar MAX

        // Con nro_venta 1 y 5 en tabla, el siguiente debe ser 6
        $this->assertSame(6, $this->sale->nextNumber());
    }

    public function test_nextNumber_considers_active_carts_to_avoid_collision(): void
    {
        // Carrito en construcción con nro_venta=3 (sin venta finalizada)
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (3, 1, 1)");

        // nextNumber debe esquivar el 3 activo en tb_carrito
        $this->assertSame(4, $this->sale->nextNumber());
    }

    public function test_nextNumber_returns_max_across_ventas_and_carrito(): void
    {
        $this->storeSale(2); // nro_venta=2 en tb_ventas
        // Carrito en curso con nro_venta=5
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (5, 1, 1)");

        // El máximo entre tb_ventas(2) y tb_carrito(5) es 5 → siguiente es 6
        $this->assertSame(6, $this->sale->nextNumber());
    }

    // -------------------------------------------------------------------------
    // storeWithStock
    // -------------------------------------------------------------------------

    public function test_storeWithStock_inserts_sale_record(): void
    {
        $result = $this->storeSale();

        $this->assertTrue($result);
        $this->assertSame(1, $this->sale->count());
    }

    public function test_storeWithStock_decrements_product_stock(): void
    {
        $this->storeSale(1, 5);

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = 1")->fetch();
        $this->assertSame(45, (int)$row['stock']);
    }

    public function test_storeWithStock_returns_false_for_empty_cart(): void
    {
        // No seedear carrito
        $result = $this->sale->storeWithStock([
            'nro_venta'    => 99,
            'id_cliente'   => 1,
            'total_pagado' => 0,
        ]);

        $this->assertFalse($result);
        $this->assertSame(0, $this->sale->count());
    }

    // -------------------------------------------------------------------------
    // destroyWithStock
    // -------------------------------------------------------------------------

    public function test_destroyWithStock_removes_sale_and_cart(): void
    {
        $this->storeSale(1, 3);

        $result = $this->sale->destroyWithStock(1);

        $this->assertTrue($result);
        $this->assertSame(0, $this->sale->count());

        $cartCount = $this->pdo->query("SELECT COUNT(*) FROM tb_carrito WHERE nro_venta = 1")->fetchColumn();
        $this->assertSame(0, (int)$cartCount);
    }

    public function test_destroyWithStock_reverts_product_stock(): void
    {
        $this->storeSale(1, 5);

        $this->sale->destroyWithStock(1);

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = 1")->fetch();
        $this->assertSame(50, (int)$row['stock']);
    }

    public function test_destroyWithStock_returns_false_for_nonexistent_id(): void
    {
        $result = $this->sale->destroyWithStock(999);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // allWithDetails / findWithDetails
    // -------------------------------------------------------------------------

    public function test_allWithDetails_returns_empty_array_on_empty_table(): void
    {
        $this->assertSame([], $this->sale->allWithDetails());
    }

    public function test_allWithDetails_returns_joined_client_data(): void
    {
        $this->storeSale();

        $rows = $this->sale->allWithDetails();
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('nombre_cliente', $rows[0]);
        $this->assertSame('Cliente A', $rows[0]['nombre_cliente']);
    }

    public function test_findWithDetails_returns_null_for_nonexistent_id(): void
    {
        $this->assertNull($this->sale->findWithDetails(999));
    }

    public function test_findWithDetails_returns_sale_with_items(): void
    {
        $this->storeSale(1, 3);

        $sale = $this->sale->findWithDetails(1);
        $this->assertIsArray($sale);
        $this->assertArrayHasKey('items', $sale);
        $this->assertCount(1, $sale['items']);
        $this->assertSame('Producto A', $sale['items'][0]['nombre']);
        $this->assertSame(3, (int)$sale['items'][0]['cantidad']);
    }

    // -------------------------------------------------------------------------
    // computeInvoiceTotals (lógica pura, no toca BD)
    // -------------------------------------------------------------------------

    public function test_computeInvoiceTotals_calculates_correctly(): void
    {
        $items = [
            ['cantidad' => 2, 'precio_venta' => 10.00],
            ['cantidad' => 3, 'precio_venta' => 5.00],
        ];

        $totals = $this->sale->computeInvoiceTotals($items);

        $this->assertEqualsWithDelta(35.0, $totals['precio_total'], 0.001);
        $this->assertSame(5, $totals['cantidad_total']);
        $this->assertEqualsWithDelta(15.0, $totals['total_unitarios'], 0.001);
    }

    public function test_computeInvoiceTotals_returns_zeros_for_empty_items(): void
    {
        $totals = $this->sale->computeInvoiceTotals([]);

        $this->assertEqualsWithDelta(0.0, $totals['precio_total'], 0.001);
        $this->assertSame(0, $totals['cantidad_total']);
        $this->assertEqualsWithDelta(0.0, $totals['total_unitarios'], 0.001);
    }
}

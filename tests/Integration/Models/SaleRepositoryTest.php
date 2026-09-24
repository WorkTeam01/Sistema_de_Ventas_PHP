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
        $this->pdo->exec("INSERT INTO tb_metodos_pago (nombre, tipo) VALUES ('Efectivo', 'efectivo'), ('Tarjeta', 'no_efectivo')");
    }

    private function seedCart(int $nroVenta, int $productId = 1, int $cantidad = 3): void
    {
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES ($nroVenta, $productId, $cantidad)");
    }

    /** Pago exacto en efectivo por el total (producto a 10.00). */
    private function payEfectivo(float $total): array
    {
        return [['id_metodo_pago' => 1, 'monto' => $total]];
    }

    private function storeSale(int $nroVenta = 1, int $cantidad = 3): array
    {
        $this->seedCart($nroVenta, 1, $cantidad);
        $total = $cantidad * 10.00;
        return $this->sale->storeWithStock(
            [
                'nro_venta'  => $nroVenta,
                'id_cliente' => 1,
            ],
            $this->payEfectivo($total)
        );
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

        $this->assertTrue($result['ok']);
        $this->assertNotNull($result['id_venta']);
        $this->assertSame(1, $this->sale->count());
    }

    public function test_storeWithStock_decrements_product_stock(): void
    {
        $this->storeSale(1, 5);

        $row = $this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = 1")->fetch();
        $this->assertSame(45, (int)$row['stock']);
    }

    public function test_storeWithStock_returns_empty_cart_error_for_empty_cart(): void
    {
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 99, 'id_cliente' => 1],
            $this->payEfectivo(10.00)
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('empty_cart', $result['error']);
        $this->assertNull($result['id_venta']);
        $this->assertSame(0, $this->sale->count());
    }

    public function test_storeWithStock_persists_precio_unitario_in_cart(): void
    {
        $this->seedCart(1, 1, 1);

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            $this->payEfectivo(10.00)
        );
        $this->assertTrue($result['ok']);

        $row = $this->pdo->query("SELECT precio_unitario FROM tb_carrito WHERE nro_venta = 1")->fetch();
        $this->assertNotNull($row['precio_unitario']);
        $this->assertEqualsWithDelta(10.00, (float)$row['precio_unitario'], 0.001);
    }

    public function test_storeWithStock_total_pagado_matches_sum_of_precio_unitario(): void
    {
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria) VALUES ('P002', 'Producto B', 8.00, 25.00, 30, 2, 100, '2026-01-01', 1, 1)");

        $this->seedCart(1, 1, 2); // 2 × 10.00 = 20.00
        $this->seedCart(1, 2, 1); // 1 × 25.00 = 25.00 → total esperado: 45.00

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            $this->payEfectivo(45.00)
        );
        $this->assertTrue($result['ok']);

        $expected = (float)$this->pdo->query(
            "SELECT SUM(cantidad * precio_unitario) FROM tb_carrito WHERE nro_venta = 1"
        )->fetchColumn();
        $sale = $this->pdo->query("SELECT total_pagado FROM tb_ventas WHERE nro_venta = 1")->fetch();
        $this->assertEqualsWithDelta($expected, (float)$sale['total_pagado'], 0.001);
        $this->assertEqualsWithDelta(45.00, (float)$sale['total_pagado'], 0.001);
    }

    public function test_findWithDetails_returns_frozen_price_after_catalog_change(): void
    {
        $this->seedCart(1, 1, 1);
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            $this->payEfectivo(10.00)
        );
        $this->assertTrue($result['ok']);

        $this->pdo->exec("UPDATE tb_almacen SET precio_venta = 99.99 WHERE id_producto = 1");
        $details = $this->sale->findWithDetails(1);

        $this->assertNotNull($details);
        $this->assertCount(1, $details['items']);
        $this->assertEqualsWithDelta(10.00, (float)$details['items'][0]['precio_venta'], 0.001);
    }

    public function test_getByNroVenta_returns_catalog_price_when_cart_is_in_progress(): void
    {
        $cartItem = new \App\Models\CartItem();
        $cartItem->addItem(1, 1, 1);

        $items = $cartItem->getByNroVenta(1);

        $this->assertCount(1, $items);
        $this->assertEqualsWithDelta(10.00, (float)$items[0]['precio_venta'], 0.001);
    }

    public function test_getByNroVenta_returns_frozen_price_for_finalized_sale(): void
    {
        $this->seedCart(1, 1, 1);
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            $this->payEfectivo(10.00)
        );
        $this->assertTrue($result['ok']);

        $this->pdo->exec("UPDATE tb_almacen SET precio_venta = 99.99 WHERE id_producto = 1");
        $cartItem = new \App\Models\CartItem();
        $items = $cartItem->getByNroVenta(1);

        $this->assertCount(1, $items);
        $this->assertEqualsWithDelta(10.00, (float)$items[0]['precio_venta'], 0.001);
    }

    public function test_invoice_subtotals_use_frozen_prices_after_catalog_change(): void
    {
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria) VALUES ('P002', 'Producto B', 8.00, 25.00, 30, 2, 100, '2026-01-01', 1, 1)");
        $this->seedCart(1, 1, 2);
        $this->seedCart(1, 2, 3);
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            $this->payEfectivo(95.00)
        );
        $this->assertTrue($result['ok']);

        $itemsBefore = $this->sale->findWithDetails(1)['items'];
        $subtotalsBefore = $this->sale->withSubtotals($itemsBefore);
        $totalsBefore = $this->sale->computeInvoiceTotals($itemsBefore);

        $this->pdo->exec("UPDATE tb_almacen SET precio_venta = 99.99 WHERE id_producto = 1");
        $this->pdo->exec("UPDATE tb_almacen SET precio_venta = 88.88 WHERE id_producto = 2");

        $itemsAfter = $this->sale->findWithDetails(1)['items'];
        $subtotalsAfter = $this->sale->withSubtotals($itemsAfter);
        $totalsAfter = $this->sale->computeInvoiceTotals($itemsAfter);

        $this->assertEquals($subtotalsBefore, $subtotalsAfter);
        $this->assertEquals($totalsBefore, $totalsAfter);
        $this->assertEqualsWithDelta(95.00, $totalsAfter['precio_total'], 0.001);
    }

    public function test_backfill_populates_null_precio_unitario_and_is_idempotent(): void
    {
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria) VALUES ('P002', 'Producto B', 8.00, 25.00, 30, 2, 100, '2026-01-01', 1, 1)");
        $this->seedCart(1, 1, 2);
        $this->seedCart(1, 2, 3);
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            $this->payEfectivo(95.00)
        );
        $this->assertTrue($result['ok']);

        $this->pdo->exec("UPDATE tb_carrito SET precio_unitario = NULL WHERE nro_venta = 1");
        $totalBefore = $this->pdo->query("SELECT total_pagado FROM tb_ventas WHERE id_venta = 1")->fetchColumn();

        $this->pdo->exec("UPDATE tb_carrito SET precio_unitario = (SELECT precio_venta FROM tb_almacen WHERE id_producto = tb_carrito.id_producto) WHERE precio_unitario IS NULL AND nro_venta IN (SELECT nro_venta FROM tb_ventas)");

        $items = $this->pdo->query("SELECT id_producto, precio_unitario FROM tb_carrito WHERE nro_venta = 1 ORDER BY id_producto")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(2, $items);
        $this->assertEqualsWithDelta(10.00, (float)$items[0]['precio_unitario'], 0.001);
        $this->assertEqualsWithDelta(25.00, (float)$items[1]['precio_unitario'], 0.001);

        $totalAfter = $this->pdo->query("SELECT total_pagado FROM tb_ventas WHERE id_venta = 1")->fetchColumn();
        $this->assertEquals($totalBefore, $totalAfter);

        $this->pdo->exec("UPDATE tb_carrito SET precio_unitario = (SELECT precio_venta FROM tb_almacen WHERE id_producto = tb_carrito.id_producto) WHERE precio_unitario IS NULL AND nro_venta IN (SELECT nro_venta FROM tb_ventas)");

        $items2 = $this->pdo->query("SELECT id_producto, precio_unitario FROM tb_carrito WHERE nro_venta = 1 ORDER BY id_producto")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals($items, $items2);
    }

    public function test_top_selling_uses_frozen_price_after_catalog_change(): void
    {
        $this->seedCart(1, 1, 2);
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            $this->payEfectivo(20.00)
        );
        $this->assertTrue($result['ok']);

        $product = new \App\Models\Product();
        $topBefore = $product->getTopSelling(5);

        $this->pdo->exec("UPDATE tb_almacen SET precio_venta = 99.99 WHERE id_producto = 1");

        $topAfter = $product->getTopSelling(5);

        $this->assertCount(1, $topBefore);
        $this->assertCount(1, $topAfter);
        $this->assertEqualsWithDelta(20.00, (float)$topBefore[0]['ingresos'], 0.001);
        $this->assertEqualsWithDelta(20.00, (float)$topAfter[0]['ingresos'], 0.001);
    }

    public function test_dashboard_aggregations_subtract_returns_by_return_period_and_keep_quantity(): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
             VALUES
                (10, 1, 1, 100.00, datetime('now')),
                (11, 1, 1, 50.00, datetime('now', '-1 month'))"
        );
        $this->pdo->exec(
            "INSERT INTO tb_devoluciones
                (nro_devolucion, id_venta, id_usuario, motivo, monto, fyh_creacion)
             VALUES
                   (1, 1, 1, 'Devolución imputada al mes anterior', 30.00, datetime('now', '-1 month')),
                   (2, 2, 1, 'Devolución imputada al mes', 20.00, datetime('now'))"
        );

        $this->assertEqualsWithDelta(80.00, $this->sale->totalCurrentMonth(1), 0.001);
        $this->assertEqualsWithDelta(20.00, $this->sale->totalPreviousMonth(1), 0.001);

        $today = $this->sale->todaySummary(1);
        $this->assertSame(1, $today['cantidad']);
        $this->assertEqualsWithDelta(80.00, $today['monto'], 0.001);

        $months = $this->sale->totalsByMonth(2, 1);
        $this->assertCount(2, $months);
        $this->assertEqualsWithDelta(20.00, (float)$months[0]['total'], 0.001);
        $this->assertEqualsWithDelta(80.00, (float)$months[1]['total'], 0.001);
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

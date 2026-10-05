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
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Vendedor', 'vendedor@test.com', 'hash', 1), ('Otro', 'otro@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente) VALUES ('Cliente A', '12345678', '70000001', 'cliente@test.com')");
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria) VALUES ('P001', 'Producto A', 5.00, 10.00, 50, 2, 100, '2026-01-01', 1, 1)");
        $this->pdo->exec("INSERT INTO tb_metodos_pago (nombre, tipo) VALUES ('Efectivo', 'efectivo'), ('Tarjeta', 'no_efectivo')");
    }

    /** Reserva abierta del POS cuyo id es el nro_venta (la venta solo se cobra sobre una reserva propia). */
    private function openReservation(int $nroVenta, int $userId = 1): void
    {
        $this->pdo->exec("INSERT OR IGNORE INTO tb_pos_reservas (id_reserva, id_usuario) VALUES ($nroVenta, $userId)");
    }

    private function seedCart(int $nroVenta, int $productId = 1, int $cantidad = 3): void
    {
        $this->openReservation($nroVenta);
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
            $this->payEfectivo($total),
            1
        );
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
        $this->openReservation(99);
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 99, 'id_cliente' => 1],
            $this->payEfectivo(10.00),
            1
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
            $this->payEfectivo(10.00),
            1
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
            $this->payEfectivo(45.00),
            1
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
            $this->payEfectivo(10.00),
            1
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
        $nro = (new \App\Models\PosReservation())->openFor(1);
        $this->assertTrue($cartItem->addItem($nro, 1, 1, 1)['ok']);

        $items = $cartItem->getByNroVenta($nro);

        $this->assertCount(1, $items);
        $this->assertEqualsWithDelta(10.00, (float)$items[0]['precio_venta'], 0.001);
    }

    public function test_getByNroVenta_returns_frozen_price_for_finalized_sale(): void
    {
        $this->seedCart(1, 1, 1);
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            $this->payEfectivo(10.00),
            1
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
            $this->payEfectivo(95.00),
            1
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
            $this->payEfectivo(95.00),
            1
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
            $this->payEfectivo(20.00),
            1
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
    // totalsByMonth — ventana de meses consecutivos desde el primer día del mes
    // -------------------------------------------------------------------------

    private function insertSaleOn(int $nroVenta, string $date, float $total): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
             VALUES ($nroVenta, 1, 1, $total, '$date')"
        );
    }

    public function test_totalsByMonth_window_from_day_31_does_not_skip_february(): void
    {
        $base = new \DateTimeImmutable('2026-03-31');
        $this->insertSaleOn(1, '2025-09-30 23:59:59', 1.00);   // fuera: un mes antes de la ventana
        $this->insertSaleOn(2, '2025-10-01 00:00:00', 10.00);  // primer instante de la ventana
        $this->insertSaleOn(3, '2025-12-31 23:00:00', 20.00);
        $this->insertSaleOn(4, '2026-02-28 12:00:00', 30.00);  // febrero: lo que la resta de meses desde el 31 saltaba
        $this->insertSaleOn(5, '2026-03-31 10:00:00', 40.00);

        $result = $this->sale->totalsByMonth(6, null, $base);

        $this->assertSame(['2025-10', '2025-12', '2026-02', '2026-03'], array_column($result, 'mes'));
        $this->assertEqualsWithDelta([10.0, 20.0, 30.0, 40.0], array_map('floatval', array_column($result, 'total')), 0.001);
    }

    public function test_totalsByMonth_window_covers_exactly_the_requested_months(): void
    {
        $base = new \DateTimeImmutable('2026-07-31');
        $this->insertSaleOn(1, '2026-01-31 23:59:59', 1.00);  // fuera (6 meses: feb..jul)
        $this->insertSaleOn(2, '2026-02-01 00:00:00', 2.00);

        $result = $this->sale->totalsByMonth(6, null, $base);

        $this->assertSame(['2026-02'], array_column($result, 'mes'));
    }

    public function test_totalsByMonth_filters_by_user_within_the_window(): void
    {
        $base = new \DateTimeImmutable('2026-03-31');
        $this->insertSaleOn(1, '2026-03-10 10:00:00', 10.00);
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion) VALUES (2, 1, 2, 99.00, '2026-03-11 10:00:00')");

        $result = $this->sale->totalsByMonth(6, 1, $base);

        $this->assertCount(1, $result);
        $this->assertEqualsWithDelta(10.0, (float)$result[0]['total'], 0.001);
    }

    // -------------------------------------------------------------------------
    // destroyWithStock
    // -------------------------------------------------------------------------

    public function test_destroyWithStock_removes_sale_and_cart(): void
    {
        $this->storeSale(1, 3);

        $result = $this->sale->destroyWithStock(1);

        $this->assertSame(['ok' => true, 'error' => null], $result);
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

    public function test_destroyWithStock_returns_not_found_for_nonexistent_id(): void
    {
        $result = $this->sale->destroyWithStock(999);

        $this->assertSame(['ok' => false, 'error' => 'not_found'], $result);
    }

    public function test_destroyWithStock_rejects_a_sale_with_returns_and_changes_nothing(): void
    {
        $this->storeSale(1, 3);
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto) VALUES (1, 1, 1, 'Defecto', 10)");

        $result = $this->sale->destroyWithStock(1);

        $this->assertSame(['ok' => false, 'error' => 'has_returns'], $result);
        $this->assertSame(1, $this->sale->count(), 'la venta sigue existiendo');
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_carrito WHERE nro_venta = 1')->fetchColumn());
        $this->assertSame(47, (int)$this->pdo->query('SELECT stock FROM tb_almacen WHERE id_producto = 1')->fetchColumn(), 'el stock no se repone');
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_devoluciones')->fetchColumn());
    }

    public function test_destroyWithStock_only_blocks_the_sale_that_has_returns(): void
    {
        $this->storeSale(1, 3);
        $this->storeSale(2, 2);
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto) VALUES (1, 1, 1, 'Defecto', 10)");

        $this->assertSame('has_returns', $this->sale->destroyWithStock(1)['error']);
        $this->assertTrue($this->sale->destroyWithStock(2)['ok']);
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
    // storeWithStock — reserva, propiedad e idempotencia del cobro
    // -------------------------------------------------------------------------

    private function stockOf(int $productId = 1): int
    {
        return (int)$this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = $productId")->fetchColumn();
    }

    private function reservationIsReleased(int $nroVenta): bool
    {
        return $this->pdo->query("SELECT fyh_liberacion FROM tb_pos_reservas WHERE id_reserva = $nroVenta")->fetchColumn() !== null;
    }

    public function test_storeWithStock_sets_id_usuario_from_the_user_argument(): void
    {
        $this->openReservation(1, 2);
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, 1, 1)");

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(10.00), 2);

        $this->assertTrue($result['ok']);
        $this->assertSame(2, (int)$this->pdo->query('SELECT id_usuario FROM tb_ventas WHERE nro_venta = 1')->fetchColumn());
    }

    public function test_storeWithStock_releases_the_reservation_in_the_same_transaction(): void
    {
        $this->seedCart(1, 1, 1);

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(10.00), 1);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['replayed']);
        $this->assertTrue($this->reservationIsReleased(1));
    }

    public function test_storeWithStock_failure_keeps_the_reservation_open(): void
    {
        $this->seedCart(1, 1, 2);

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(5.00), 1);

        $this->assertSame('faltante', $result['error']);
        $this->assertFalse($this->reservationIsReleased(1));
    }

    public function test_storeWithStock_rejects_a_reservation_of_another_user(): void
    {
        $this->seedCart(1, 1, 1);

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(10.00), 2);

        $this->assertFalse($result['ok']);
        $this->assertSame('forbidden', $result['error']);
        $this->assertSame(50, $this->stockOf());
        $this->assertFalse($this->reservationIsReleased(1));
    }

    public function test_storeWithStock_rejects_a_number_without_reservation(): void
    {
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (7, 1, 1)");

        $result = $this->sale->storeWithStock(['nro_venta' => 7, 'id_cliente' => 1], $this->payEfectivo(10.00), 1);

        $this->assertSame('forbidden', $result['error']);
        $this->assertSame(0, $this->sale->count());
    }

    public function test_storeWithStock_rejects_a_cancelled_reservation_without_sale(): void
    {
        $this->seedCart(1, 1, 1);
        $this->pdo->exec('UPDATE tb_pos_reservas SET fyh_liberacion = CURRENT_TIMESTAMP WHERE id_reserva = 1');

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(10.00), 1);

        $this->assertSame('forbidden', $result['error']);
        $this->assertSame(0, $this->sale->count());
        $this->assertSame(50, $this->stockOf());
    }

    public function test_storeWithStock_replay_returns_the_original_sale_without_touching_anything(): void
    {
        $this->seedCart(1, 1, 3);
        $first = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 1);
        $this->assertTrue($first['ok']);
        $this->pdo->exec('UPDATE tb_almacen SET precio_venta = 99.99 WHERE id_producto = 1');

        $second = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 1);

        $this->assertTrue($second['ok']);
        $this->assertTrue($second['replayed']);
        $this->assertSame($first['id_venta'], $second['id_venta']);
        $this->assertSame(1, $this->sale->count());
        $this->assertSame(47, $this->stockOf(), 'el stock no se descuenta otra vez');
        $price = (float)$this->pdo->query('SELECT precio_unitario FROM tb_carrito WHERE nro_venta = 1')->fetchColumn();
        $this->assertEqualsWithDelta(10.00, $price, 0.001, 'el precio congelado no se vuelve a fijar');
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pagos')->fetchColumn());
    }

    public function test_storeWithStock_replay_recomputes_the_change_from_stored_payments(): void
    {
        $this->seedCart(1, 1, 3);
        $first = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(100.00), 1);
        $this->assertEqualsWithDelta(70.00, $first['vuelto'], 0.001);

        $second = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 1);

        $this->assertTrue($second['replayed']);
        $this->assertEqualsWithDelta(70.00, $second['vuelto'], 0.001);
    }

    public function test_storeWithStock_resending_another_users_finalized_number_is_forbidden(): void
    {
        $this->seedCart(1, 1, 3);
        $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 1);

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 2);

        $this->assertFalse($result['ok']);
        $this->assertSame('forbidden', $result['error']);
        $this->assertSame(1, $this->sale->count());
        $this->assertSame(47, $this->stockOf());
    }

    /**
     * Sale cuya primera lectura de "venta ya existente" devuelve null: es lo que ve el envío que perdió
     * la carrera, con la instantánea de su transacción tomada antes de que el otro confirmara.
     */
    private function saleWithStaleFirstRead(): Sale
    {
        return new class extends Sale {
            private int $reads = 0;

            protected function findFinalized(int $nroVenta): ?array
            {
                return $this->reads++ === 0 ? null : parent::findFinalized($nroVenta);
            }
        };
    }

    public function test_storeWithStock_losing_a_simultaneous_resend_replays_the_confirmed_sale(): void
    {
        $this->seedCart(1, 1, 3);
        $first = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 1);
        $this->assertTrue($first['ok']);

        $second = $this->saleWithStaleFirstRead()
            ->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 1);

        $this->assertTrue($second['ok'], 'el reenvío simultáneo no puede ser forbidden');
        $this->assertTrue($second['replayed']);
        $this->assertSame($first['id_venta'], $second['id_venta']);
        $this->assertSame(1, $this->sale->count());
        $this->assertSame(47, $this->stockOf(), 'el stock no se descuenta otra vez');
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pagos')->fetchColumn());
    }

    public function test_storeWithStock_losing_a_simultaneous_resend_of_another_users_sale_is_forbidden(): void
    {
        $this->seedCart(1, 1, 3);
        $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 1);

        $result = $this->saleWithStaleFirstRead()
            ->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->payEfectivo(30.00), 2);

        $this->assertFalse($result['ok']);
        $this->assertSame('forbidden', $result['error']);
        $this->assertSame(1, $this->sale->count());
        $this->assertSame(47, $this->stockOf());
    }

    public function test_storeWithStock_a_number_never_reserved_and_never_sold_is_forbidden(): void
    {
        $result = $this->saleWithStaleFirstRead()
            ->storeWithStock(['nro_venta' => 77, 'id_cliente' => 1], $this->payEfectivo(10.00), 1);

        $this->assertFalse($result['ok']);
        $this->assertSame('forbidden', $result['error']);
        $this->assertSame(0, $this->sale->count());
        $this->assertSame(50, $this->stockOf());
    }

    public function test_storeWithStock_database_rejects_two_sales_with_the_same_number(): void
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES (5, 1, 10)");

        $this->expectException(\PDOException::class);
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES (5, 1, 10)");
    }

    // -------------------------------------------------------------------------
    // Venta sin cliente (Consumidor final)
    // -------------------------------------------------------------------------

    private function storeSaleSinCliente(int $nroVenta = 1, int $cantidad = 3): array
    {
        $this->seedCart($nroVenta, 1, $cantidad);
        $total = $cantidad * 10.00;
        return $this->sale->storeWithStock(
            [
                'nro_venta'  => $nroVenta,
                'id_cliente' => null,
            ],
            $this->payEfectivo($total),
            1
        );
    }

    public function test_storeWithStock_creates_sale_without_client(): void
    {
        $result = $this->storeSaleSinCliente();

        $this->assertTrue($result['ok']);
        $this->assertNotNull($result['id_venta']);

        $row = $this->pdo->query("SELECT id_cliente FROM tb_ventas WHERE nro_venta = 1")->fetch();
        $this->assertNull($row['id_cliente']);
    }

    public function test_storeWithStock_treats_id_cliente_zero_as_null(): void
    {
        $this->seedCart(1, 1, 3);
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 0],
            $this->payEfectivo(30.00),
            1
        );

        $this->assertTrue($result['ok']);
        $row = $this->pdo->query("SELECT id_cliente FROM tb_ventas WHERE nro_venta = 1")->fetch();
        $this->assertNull($row['id_cliente']);
    }

    public function test_allWithDetails_includes_sales_without_client(): void
    {
        $this->storeSaleSinCliente();

        $rows = $this->sale->allWithDetails();
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey('nombre_cliente', $rows[0]);
        $this->assertNull($rows[0]['nombre_cliente']);
    }

    public function test_findWithDetails_returns_client_fields_null_without_client(): void
    {
        $this->storeSaleSinCliente();

        $sale = $this->sale->findWithDetails(1);
        $this->assertIsArray($sale);
        $this->assertNull($sale['nombre_cliente']);
        $this->assertNull($sale['nit_ci_cliente']);
        $this->assertCount(1, $sale['items']);
    }

    public function test_latest_includes_sales_without_client(): void
    {
        $this->storeSaleSinCliente();

        $rows = $this->sale->latest(5);
        $this->assertCount(1, $rows);
        $this->assertNull($rows[0]['nombre_cliente']);
    }

    // -------------------------------------------------------------------------
    // Ventas históricas: total_pagado difiere de las líneas a precio congelado
    // -------------------------------------------------------------------------

    private function seedHistoricalSale(int $nroVenta, float $lineTotal, float $totalPagado): int
    {
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_reserva, id_usuario, fyh_liberacion) VALUES ($nroVenta, 1, CURRENT_TIMESTAMP)");
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario) VALUES ($nroVenta, 1, 2, " . ($lineTotal / 2) . ")");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado) VALUES ($nroVenta, 1, 1, $totalPagado)");

        return (int)$this->pdo->lastInsertId();
    }

    /** @dataProvider-free: positivo y negativo comparten la misma comprobación */
    private function assertTableSumsToTotalPagado(int $idVenta, float $expectedAdjustment): void
    {
        $sale = $this->sale->findWithDetails($idVenta);
        $items = $this->sale->withSubtotals($sale['items']);
        $totals = $this->sale->computeInvoiceTotals($items, (float)$sale['total_pagado']);

        $this->assertEqualsWithDelta($expectedAdjustment, $totals['ajuste'], 0.001);
        $linesSum = array_sum(array_column($items, 'subtotal'));
        $this->assertEqualsWithDelta((float)$sale['total_pagado'], $linesSum + $totals['ajuste'], 0.001);
        $this->assertEqualsWithDelta((float)$sale['total_pagado'], $totals['total_pagado'], 0.001);
    }

    public function test_historical_sale_with_positive_adjustment_sums_to_total_pagado(): void
    {
        $id = $this->seedHistoricalSale(20, 20.00, 25.00);

        $this->assertTableSumsToTotalPagado($id, 5.00);
    }

    public function test_historical_sale_with_negative_adjustment_sums_to_total_pagado(): void
    {
        $id = $this->seedHistoricalSale(21, 20.00, 15.00);

        $this->assertTableSumsToTotalPagado($id, -5.00);
    }

    public function test_sale_without_difference_has_no_adjustment(): void
    {
        $this->storeSale(1, 3);
        $id = (int)$this->pdo->query('SELECT id_venta FROM tb_ventas WHERE nro_venta = 1')->fetchColumn();

        $this->assertTableSumsToTotalPagado($id, 0.0);
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

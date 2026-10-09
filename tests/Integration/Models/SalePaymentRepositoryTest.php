<?php

namespace Tests\Integration\Models;

use App\Models\Sale;
use App\Models\SalePayment;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class SalePaymentRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Sale $sale;
    private SalePayment $payments;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->sale = new Sale();
        $this->payments = new SalePayment();
        $this->seedDependencies();
        $this->seedMetodos();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function seedDependencies(): void
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
    }

    private function seedMetodos(): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_metodos_pago (nombre, tipo) VALUES
                ('Efectivo', 'efectivo'),
                ('Tarjeta', 'no_efectivo'),
                ('Transferencia bancaria', 'no_efectivo'),
                ('QR', 'no_efectivo')"
        );
    }

    /** Carrito listo para storeWithStock (total = cantidad × 12.50). */
    private function seedCart(int $nroVenta, int $cantidad = 5): void
    {
        $this->pdo->exec("INSERT OR IGNORE INTO tb_pos_reservas (id_reserva, id_usuario) VALUES ($nroVenta, 1)");
        $this->pdo->exec(
            "INSERT INTO tb_carrito (nro_venta, id_producto, cantidad)
             VALUES ($nroVenta, 1, $cantidad)"
        );
    }

    /**
     * Venta ya finalizada (para tests de byVenta que no pasan por storeWithStock).
     *
     * @return int id_venta insertado.
     */
    private function seedVentaFinalizada(int $nroVenta = 10, float $total = 62.50): int
    {
        $this->pdo->exec(
            "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado)
             VALUES ($nroVenta, 1, 1, $total)"
        );
        return (int)$this->pdo->lastInsertId();
    }

    private function insertPago(int $idVenta, int $idMetodo, float $monto): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_pagos (id_venta, id_metodo_pago, monto)
             VALUES ($idVenta, $idMetodo, $monto)"
        );
    }

    private function countVentas(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM tb_ventas")->fetchColumn();
    }

    private function countPagos(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM tb_pagos")->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // byVenta — líneas con nombre/tipo del método
    // -------------------------------------------------------------------------

    public function test_byVenta_returns_empty_array_when_sale_has_no_payments(): void
    {
        $idVenta = $this->seedVentaFinalizada();

        $this->assertSame([], $this->payments->byVenta($idVenta));
    }

    public function test_byVenta_returns_lines_with_method_name_and_type(): void
    {
        $idVenta = $this->seedVentaFinalizada();
        $this->insertPago($idVenta, 1, 30.00);
        $this->insertPago($idVenta, 2, 32.50);

        $lines = $this->payments->byVenta($idVenta);

        $this->assertCount(2, $lines);

        $this->assertSame('Efectivo', $lines[0]['nombre']);
        $this->assertSame('efectivo', $lines[0]['tipo']);
        $this->assertEqualsWithDelta(30.00, (float)$lines[0]['monto'], 0.001);

        $this->assertSame('Tarjeta', $lines[1]['nombre']);
        $this->assertSame('no_efectivo', $lines[1]['tipo']);
        $this->assertEqualsWithDelta(32.50, (float)$lines[1]['monto'], 0.001);
    }

    public function test_byVenta_is_scoped_to_requested_sale(): void
    {
        $id1 = $this->seedVentaFinalizada(10, 62.50);
        $id2 = $this->seedVentaFinalizada(11, 20.00);
        $this->insertPago($id1, 1, 62.50);
        $this->insertPago($id2, 1, 20.00);

        $lines1 = $this->payments->byVenta($id1);
        $lines2 = $this->payments->byVenta($id2);

        $this->assertCount(1, $lines1);
        $this->assertCount(1, $lines2);
        $this->assertEqualsWithDelta(62.50, (float)$lines1[0]['monto'], 0.001);
        $this->assertEqualsWithDelta(20.00, (float)$lines2[0]['monto'], 0.001);
    }

    // -------------------------------------------------------------------------
    // storeWithStock — matriz de cobro
    // -------------------------------------------------------------------------

    public function test_storeWithStock_exact_payment_ok_without_vuelto(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [['id_metodo_pago' => 1, 'monto' => 62.50]],
            1
        );

        $this->assertTrue($result['ok']);
        $this->assertNotNull($result['id_venta']);
        $this->assertSame(0.0, $result['vuelto']);
        $this->assertNull($result['error']);

        $lines = $this->payments->byVenta((int)$result['id_venta']);
        $this->assertCount(1, $lines);
        $this->assertEqualsWithDelta(62.50, (float)$lines[0]['monto'], 0.001);
    }

    public function test_storeWithStock_mixed_payment_persists_one_line_per_method(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [
                ['id_metodo_pago' => 1, 'monto' => 30.00],
                ['id_metodo_pago' => 2, 'monto' => 32.50],
            ],
            1
        );

        $this->assertTrue($result['ok']);
        $this->assertSame(0.0, $result['vuelto']);

        $lines = $this->payments->byVenta((int)$result['id_venta']);
        $this->assertCount(2, $lines);
        $this->assertSame('Efectivo', $lines[0]['nombre']);
        $this->assertSame('efectivo', $lines[0]['tipo']);
        $this->assertEqualsWithDelta(30.00, (float)$lines[0]['monto'], 0.001);
        $this->assertSame('Tarjeta', $lines[1]['nombre']);
        $this->assertSame('no_efectivo', $lines[1]['tipo']);
        $this->assertEqualsWithDelta(32.50, (float)$lines[1]['monto'], 0.001);
    }

    public function test_storeWithStock_faltante_rejects_without_sale_or_payments(): void
    {
        $this->seedCart(1, 5); // total = 62.50
        $ventasBefore = $this->countVentas();

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [['id_metodo_pago' => 1, 'monto' => 50.00]],
            1
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('faltante', $result['error']);
        $this->assertNotNull($result['faltante']);
        $this->assertEqualsWithDelta(12.50, $result['faltante'], 0.001);
        $this->assertNull($result['id_venta']);
        $this->assertSame($ventasBefore, $this->countVentas());
        $this->assertSame(0, $this->countPagos());
    }

    public function test_storeWithStock_exceso_sin_efectivo_rejects(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [['id_metodo_pago' => 2, 'monto' => 70.00]], // solo tarjeta
            1
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('exceso_sin_efectivo', $result['error']);
        $this->assertNull($result['id_venta']);
        $this->assertSame(0, $this->countVentas());
        $this->assertSame(0, $this->countPagos());
    }

    public function test_storeWithStock_efectivo_exceso_returns_vuelto_not_persisted(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [['id_metodo_pago' => 1, 'monto' => 70.00]],
            1
        );

        $this->assertTrue($result['ok']);
        $this->assertEqualsWithDelta(7.50, $result['vuelto'], 0.001);

        // El vuelto NO se persiste como línea: solo la línea de efectivo 70.00
        $lines = $this->payments->byVenta((int)$result['id_venta']);
        $this->assertCount(1, $lines);
        $this->assertEqualsWithDelta(70.00, (float)$lines[0]['monto'], 0.001);
        $this->assertSame(1, $this->countPagos());
    }

    public function test_storeWithStock_same_method_twice_sums_into_one_line(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [
                ['id_metodo_pago' => 2, 'monto' => 30.00],
                ['id_metodo_pago' => 2, 'monto' => 32.50],
            ],
            1
        );

        $this->assertTrue($result['ok']);

        $lines = $this->payments->byVenta((int)$result['id_venta']);
        $this->assertCount(1, $lines);
        $this->assertSame('Tarjeta', $lines[0]['nombre']);
        $this->assertEqualsWithDelta(62.50, (float)$lines[0]['monto'], 0.001);
    }

    public function test_storeWithStock_sin_metodos_when_catalog_empty_or_all_deactivated(): void
    {
        $this->seedCart(1, 5);
        $this->pdo->exec("UPDATE tb_metodos_pago SET activo = 0");

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [['id_metodo_pago' => 1, 'monto' => 62.50]],
            1
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('sin_metodos', $result['error']);
        $this->assertSame(0, $this->countVentas());
    }

    public function test_storeWithStock_empty_cart_returns_empty_cart(): void
    {
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_reserva, id_usuario) VALUES (99, 1)");

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 99, 'id_cliente' => 1],
            [['id_metodo_pago' => 1, 'monto' => 10.00]],
            1
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('empty_cart', $result['error']);
    }

    public function test_storeWithStock_invalid_payment_rejects(): void
    {
        $this->seedCart(1, 5);

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [['id_metodo_pago' => 1, 'monto' => 0]],
            1
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_payment', $result['error']);
        $this->assertSame(0, $this->countVentas());
    }

    public function test_storeWithStock_invalid_method_rejects(): void
    {
        $this->seedCart(1, 5);

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [['id_metodo_pago' => 999, 'monto' => 62.50]],
            1
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_payment', $result['error']);
        $this->assertSame(0, $this->countVentas());
    }

    public function test_storeWithStock_deactivated_method_rejects(): void
    {
        $this->seedCart(1, 5);
        $this->pdo->exec("UPDATE tb_metodos_pago SET activo = 0 WHERE id_metodo_pago = 2");
        // id 1 (Efectivo) sigue activo; usar método desactivado → invalid_payment

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [['id_metodo_pago' => 2, 'monto' => 62.50]],
            1
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_payment', $result['error']);
        $this->assertSame(0, $this->countVentas());
    }

    public function test_storeWithStock_empty_payments_with_active_methods_returns_invalid_payment(): void
    {
        $this->seedCart(1, 5); // total = 62.50, catálogo OK pero sin líneas de pago

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [],
            1
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('invalid_payment', $result['error']);
        $this->assertSame(0, $this->countVentas());
    }

    public function test_storeWithStock_mixed_efectivo_exceso_with_other_method_ok(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        // 40 efectivo + 30 tarjeta = 70 > 62.50, hay línea efectivo → ok, vuelto 7.50
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [
                ['id_metodo_pago' => 1, 'monto' => 40.00],
                ['id_metodo_pago' => 2, 'monto' => 30.00],
            ],
            1
        );

        $this->assertTrue($result['ok']);
        $this->assertEqualsWithDelta(7.50, $result['vuelto'], 0.001);

        $lines = $this->payments->byVenta((int)$result['id_venta']);
        $this->assertCount(2, $lines);
    }

    // -------------------------------------------------------------------------
    // storeWithStock — montos en céntimos, tope de vuelto y límites del total
    // -------------------------------------------------------------------------

    /** Producto extra con stock holgado; devuelve su id. */
    private function seedProduct(string $codigo, string $price, int $stock = 10): int
    {
        $this->pdo->exec(
            "INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo,
                 stock_maximo, fecha_ingreso, id_usuario, id_categoria)
             VALUES ('$codigo', 'Extra $codigo', 1.00, $price, $stock, 1, 200000000, '2026-01-01', 1, 1)"
        );

        return (int)$this->pdo->lastInsertId();
    }

    private function seedCartOf(int $nroVenta, int $productId, int $cantidad): void
    {
        $this->pdo->exec("INSERT OR IGNORE INTO tb_pos_reservas (id_reserva, id_usuario) VALUES ($nroVenta, 1)");
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES ($nroVenta, $productId, $cantidad)");
    }

    private function pay(array $lines): array
    {
        $payments = [];
        foreach ($lines as $idMetodo => $monto) {
            $payments[] = ['id_metodo_pago' => $idMetodo, 'monto' => $monto];
        }

        return $payments;
    }

    public function test_storeWithStock_rejects_change_larger_than_the_cash_received(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        // tarjeta 100 + efectivo 1 = 101 → vuelto 38.50 pero solo entró 1.00 en efectivo
        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([2 => 100.00, 1 => 1.00]), 1);

        $this->assertFalse($result['ok']);
        $this->assertSame('vuelto_excede_efectivo', $result['error']);
        $this->assertSame(0, $this->countVentas());
        $this->assertSame(0, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pagos')->fetchColumn());
    }

    public function test_storeWithStock_change_comes_from_cash_in_a_mixed_payment(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([2 => 30.00, 1 => 50.00]), 1);

        $this->assertTrue($result['ok']);
        $this->assertEqualsWithDelta(17.50, $result['vuelto'], 0.001);
    }

    public function test_storeWithStock_change_equal_to_the_cash_received_is_accepted(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([2 => 62.50, 1 => 10.00]), 1);

        $this->assertTrue($result['ok']);
        $this->assertEqualsWithDelta(10.00, $result['vuelto'], 0.001);
    }

    public function test_storeWithStock_amounts_are_rounded_per_line_before_validating_and_saving(): void
    {
        $p = $this->seedProduct('P-10', '10.00');
        $this->seedCartOf(1, $p, 2); // total = 20.00

        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [
                ['id_metodo_pago' => 1, 'monto' => '10.004'],
                ['id_metodo_pago' => 1, 'monto' => '10.004'],
            ],
            1
        );

        $this->assertTrue($result['ok'], (string)$result['error']);
        $this->assertEqualsWithDelta(0.0, $result['vuelto'], 0.0001);
        $stored = $this->pdo->query('SELECT monto FROM tb_pagos')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertCount(1, $stored);
        $this->assertEqualsWithDelta(20.00, (float)$stored[0], 0.0001);
    }

    public function test_storeWithStock_accepts_amounts_with_comma_as_text(): void
    {
        $this->seedCart(1, 5); // total = 62.50

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([1 => '62,50']), 1);

        $this->assertTrue($result['ok']);
    }

    public function test_storeWithStock_float_sums_do_not_create_false_shortfalls(): void
    {
        $p = $this->seedProduct('P-33', '33.30');
        $this->seedCartOf(1, $p, 1); // total = 33.30

        // 11.10 + 22.20 en float da 33.300000000000004
        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([1 => 11.10, 2 => 22.20]), 1);

        $this->assertTrue($result['ok'], (string)$result['error']);
        $this->assertEqualsWithDelta(0.0, $result['vuelto'], 0.0001);
    }

    public function test_storeWithStock_rejects_non_numeric_amount_as_invalid_payment(): void
    {
        $this->seedCart(1, 5);

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([1 => 'abc']), 1);

        $this->assertSame('invalid_payment', $result['error']);
    }

    public function test_storeWithStock_rejects_zero_total_without_creating_anything(): void
    {
        $p = $this->seedProduct('P-0', '0.00');
        $this->seedCartOf(1, $p, 1);

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([1 => 0.00]), 1);

        $this->assertFalse($result['ok']);
        $this->assertSame('total_cero', $result['error']);
        $this->assertSame(0, $this->countVentas());
        $this->assertSame(0, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pagos')->fetchColumn());
        $this->assertNull(
            $this->pdo->query('SELECT fyh_liberacion FROM tb_pos_reservas WHERE id_reserva = 1')->fetchColumn(),
            'la reserva sigue abierta'
        );
    }

    public function test_storeWithStock_rejects_total_above_the_column_maximum(): void
    {
        $p = $this->seedProduct('P-BIG', '60000000.00');
        $this->seedCartOf(1, $p, 2); // 120.000.000,00 > 99.999.999,99

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([1 => 120000000]), 1);

        $this->assertFalse($result['ok']);
        $this->assertSame('total_excede', $result['error']);
        $this->assertSame(0, $this->countVentas());
        $this->assertSame(10, (int)$this->pdo->query("SELECT stock FROM tb_almacen WHERE id_producto = $p")->fetchColumn());
    }

    public function test_storeWithStock_accepts_a_total_exactly_at_the_maximum(): void
    {
        $p = $this->seedProduct('P-MAX', '99999999.99');
        $this->seedCartOf(1, $p, 1);

        $result = $this->sale->storeWithStock(['nro_venta' => 1, 'id_cliente' => 1], $this->pay([1 => '99999999.99']), 1);

        $this->assertTrue($result['ok'], (string)$result['error']);
    }

    // -------------------------------------------------------------------------
    // findWithDetails — adjunta payments/vuelto
    // -------------------------------------------------------------------------

    public function test_findWithDetails_returns_empty_payments_and_zero_vuelto_for_historical_sale(): void
    {
        $idVenta = $this->seedVentaFinalizada(10, 62.50);

        $sale = $this->sale->findWithDetails($idVenta);

        $this->assertIsArray($sale);
        $this->assertArrayHasKey('payments', $sale);
        $this->assertSame([], $sale['payments']);
        $this->assertSame(0.0, $sale['vuelto']);
    }

    public function test_findWithDetails_returns_payments_and_vuelto_for_sale_with_payments(): void
    {
        $this->seedCart(1, 5); // total = 62.50
        $result = $this->sale->storeWithStock(
            ['nro_venta' => 1, 'id_cliente' => 1],
            [
                ['id_metodo_pago' => 1, 'monto' => 40.00],
                ['id_metodo_pago' => 2, 'monto' => 30.00],
            ],
            1
        );
        $this->assertTrue($result['ok']);

        $sale = $this->sale->findWithDetails((int)$result['id_venta']);

        $this->assertIsArray($sale);
        $this->assertCount(2, $sale['payments']);
        $this->assertSame('Efectivo', $sale['payments'][0]['nombre']);
        $this->assertEqualsWithDelta(40.00, (float)$sale['payments'][0]['monto'], 0.001);
        $this->assertSame('Tarjeta', $sale['payments'][1]['nombre']);
        $this->assertEqualsWithDelta(30.00, (float)$sale['payments'][1]['monto'], 0.001);
        // suma 70 − total 62.50 = 7.50
        $this->assertEqualsWithDelta(7.50, $sale['vuelto'], 0.001);
    }

    // -------------------------------------------------------------------------
    // Backfill idempotente
    // -------------------------------------------------------------------------

    /** Extrae el INSERT…SELECT de backfill de la migración 008 (fuente única). */
    private function backfillSql(): string
    {
        $migration = file_get_contents(
            __DIR__ . '/../../../database/migrations/008_formas_de_pago.sql'
        );
        if (
            preg_match(
                '/INSERT INTO `tb_pagos`.*?(?=\z)/s',
                $migration,
                $m
            )
        ) {
            return trim($m[0]);
        }
        $this->fail('Backfill SQL no encontrado en migrations/008_formas_de_pago.sql');
    }

    public function test_backfill_is_idempotent_and_leaves_existing_lines_intact(): void
    {
        $idHistorica = $this->seedVentaFinalizada(20, 100.00); // sin pagos → backfill
        $idPagada = $this->seedVentaFinalizada(21, 50.00);     // ya con línea Tarjeta
        $this->insertPago($idPagada, 2, 50.00);

        $sql = $this->backfillSql();
        $this->pdo->exec($sql);
        $this->pdo->exec($sql); // segunda corrida: no debe duplicar

        // Histórica: exactamente 1 línea Efectivo con monto = total
        $lines = $this->payments->byVenta($idHistorica);
        $this->assertCount(1, $lines);
        $this->assertSame('Efectivo', $lines[0]['nombre']);
        $this->assertSame('efectivo', $lines[0]['tipo']);
        $this->assertEqualsWithDelta(100.00, (float)$lines[0]['monto'], 0.001);

        // Ya pagada: línea preexistente intacta, sin línea de backfill
        $linesPagada = $this->payments->byVenta($idPagada);
        $this->assertCount(1, $linesPagada);
        $this->assertSame('Tarjeta', $linesPagada[0]['nombre']);
        $this->assertEqualsWithDelta(50.00, (float)$linesPagada[0]['monto'], 0.001);

        // Total global: 1 backfill + 1 preexistente = 2 (no 3+ tras re-ejecutar)
        $this->assertSame(2, $this->countPagos());
    }
}

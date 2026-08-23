<?php

namespace Tests\Integration\Models;

use App\Models\Sale;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Cubre métodos de Sale con funciones de fecha específicas de MySQL
 * (CURDATE(), YEAR(), MONTH(), DATE_FORMAT()) que SQLite in-memory no soporta.
 * Ver SaleRepositoryTest para el resto de la cobertura (SQLite).
 */
final class SaleRepositoryMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private Sale $sale;
    private static int $nroVenta = 1;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->sale = new Sale();
        self::$nroVenta = 1;

        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES
            ('Vendedor Uno', 'v1@test.com', 'hash', 1),
            ('Vendedor Dos', 'v2@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Cliente Test', '12345678', '70000000', 'cliente@example.com')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P001', 'Producto A', 5.00, 10.00, 50, 2, 100, '2026-01-01', 1, 1)");
    }

    /**
     * $sqlDate es una expresión SQL (NOW(), NOW() - INTERVAL 1 MONTH, ...) evaluada
     * por el propio MariaDB, no una fecha calculada con date()/strtotime() de PHP
     * — el reloj/timezone de PHP y el del servidor MariaDB pueden no coincidir.
     */
    private function createSale(float $total, string $sqlDate, ?int $userId = null): void
    {
        $nroVenta = self::$nroVenta++;
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES ($nroVenta, 1, 1)");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
            VALUES ($nroVenta, 1, " . ($userId ?? 'NULL') . ", $total, $sqlDate)");
    }

    public function test_totalCurrentMonth_sums_only_current_month_sales(): void
    {
        $this->createSale(100.00, 'NOW()');
        $this->createSale(50.00, 'NOW() - INTERVAL 1 MONTH');

        $this->assertSame(100.00, $this->sale->totalCurrentMonth());
    }

    public function test_totalPreviousMonth_sums_only_previous_month_sales(): void
    {
        $this->createSale(100.00, 'NOW()');
        $this->createSale(75.00, 'NOW() - INTERVAL 1 MONTH');

        $this->assertSame(75.00, $this->sale->totalPreviousMonth());
    }

    public function test_todaySummary_counts_only_todays_sales(): void
    {
        $this->createSale(100.00, 'NOW()');
        $this->createSale(50.00, 'NOW() - INTERVAL 1 DAY');

        $summary = $this->sale->todaySummary();

        $this->assertSame(1, $summary['cantidad']);
        $this->assertSame(100.00, $summary['monto']);
    }

    public function test_totalsByMonth_groups_by_month(): void
    {
        $this->createSale(100.00, 'NOW()');
        $this->createSale(50.00, 'NOW() - INTERVAL 1 MONTH');

        $result = $this->sale->totalsByMonth(2);

        $this->assertCount(2, $result);
        $this->assertSame(50.00, (float)$result[0]['total']);
        $this->assertSame(100.00, (float)$result[1]['total']);
    }

    public function test_totalCurrentMonth_filters_by_userId(): void
    {
        $this->createSale(100.00, 'NOW()', 1);
        $this->createSale(50.00, 'NOW()', 2);

        $this->assertSame(100.00, $this->sale->totalCurrentMonth(1));
    }
}

<?php

namespace Tests\Integration\Models;

use App\Models\Report;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Fila de devoluciones de períodos anteriores y ticket neto contra MariaDB real,
 * con el período y las fechas de las ventas calculados por el propio motor.
 */
final class ReportReturnsMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private Report $report;
    private string $from;
    private string $to;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->report = new Report();

        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Vendedor Uno', 'v1@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Cliente Test', '12345678', '70000000', 'cliente@example.com')");

        $bounds = $this->pdo->query(
            "SELECT DATE_FORMAT(CURDATE(), '%Y-%m-01 00:00:00') AS d,
                    CONCAT(LAST_DAY(CURDATE()), ' 23:59:59')    AS h"
        )->fetch(\PDO::FETCH_ASSOC);
        $this->from = $bounds['d'];
        $this->to   = $bounds['h'];
    }

    private function createSale(int $nro, float $total, string $sqlDate): int
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
            VALUES ($nro, 1, 1, $total, $sqlDate)");
        return (int)$this->pdo->lastInsertId();
    }

    private function createReturn(int $nro, int $saleId, float $monto): void
    {
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, id_usuario, motivo, monto, fyh_creacion)
            VALUES ($nro, $saleId, 1, 'Prueba', $monto, NOW())");
    }

    public function test_outside_row_plus_listed_rows_sum_to_net_total(): void
    {
        $previous = $this->createSale(1, 100.00, 'NOW() - INTERVAL 2 MONTH');
        $current  = $this->createSale(2, 200.00, 'NOW()');
        $this->createReturn(1, $previous, 30.00);
        $this->createReturn(2, $current, 50.00);

        $rows    = $this->report->salesByPeriod($this->from, $this->to);
        $listed  = array_sum(array_map(fn($r) => (float)$r['total_pagado'] - (float)$r['monto_devuelto'], $rows));
        $outside = $this->report->returnsOutsideListed($this->from, $this->to);
        $total   = (float)$this->report->salesTotals($this->from, $this->to)['total_ingresos'];

        $this->assertEqualsWithDelta(30.00, $outside, 0.001);
        $this->assertEqualsWithDelta(120.00, $total, 0.001);
        $this->assertEqualsWithDelta($total, $listed - $outside, 0.001);
    }

    public function test_ticket_is_net_total_over_sales_and_zero_without_sales(): void
    {
        $this->assertEqualsWithDelta(0.0, (float)$this->report->salesTotals($this->from, $this->to)['ticket_promedio'], 0.001);

        $a = $this->createSale(1, 100.00, 'NOW()');
        $this->createSale(2, 200.00, 'NOW()');
        $this->createReturn(1, $a, 40.00);

        $this->assertEqualsWithDelta(130.00, (float)$this->report->salesTotals($this->from, $this->to)['ticket_promedio'], 0.001);
    }
}

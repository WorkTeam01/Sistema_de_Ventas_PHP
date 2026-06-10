<?php

namespace Tests\Integration\Models;

use App\Models\Report;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class ReportClientsTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Report $report;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->report = new Report();
        $this->seedDependencies();
    }

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
                          VALUES ('Cliente Alpha', '11111111', '70000001', 'alpha@test.com'),
                                 ('Cliente Beta',  '22222222', '70000002', 'beta@test.com'),
                                 ('Cliente Gamma', '33333333', '70000003', 'gamma@test.com')");
    }

    private function seedVenta(int $nroVenta, int $idCliente, float $total, string $fyh): void
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, total_pagado, fyh_creacion)
                          VALUES ({$nroVenta}, {$idCliente}, {$total}, '{$fyh}')");
    }

    // ── clientsByPeriod ───────────────────────────────────────────────────────

    public function test_clientsByPeriod_returns_clients_ordered_by_monto_acumulado_desc(): void
    {
        // Alpha: Bs. 300, Beta: Bs. 500
        $this->seedVenta(1, 1, 300.00, '2025-06-10 10:00:00');
        $this->seedVenta(2, 2, 500.00, '2025-06-15 10:00:00');

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(2, $rows);
        $this->assertSame('Cliente Beta', $rows[0]['cliente']);
        $this->assertEqualsWithDelta(500.00, (float)$rows[0]['monto_acumulado'], 0.01);
        $this->assertSame('Cliente Alpha', $rows[1]['cliente']);
    }

    public function test_clientsByPeriod_counts_multiple_purchases_correctly(): void
    {
        // Alpha hace 3 compras
        $this->seedVenta(1, 1, 100.00, '2025-06-01 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-10 10:00:00');
        $this->seedVenta(3, 1, 150.00, '2025-06-20 10:00:00');

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertSame(3, (int)$rows[0]['num_compras']);
        $this->assertEqualsWithDelta(450.00, (float)$rows[0]['monto_acumulado'], 0.01);
    }

    public function test_clientsByPeriod_ultima_compra_is_max_date(): void
    {
        $this->seedVenta(1, 1, 100.00, '2025-06-05 10:00:00');
        $this->seedVenta(2, 1, 200.00, '2025-06-25 15:30:00');
        $this->seedVenta(3, 1, 150.00, '2025-06-12 08:00:00');

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame('2025-06-25 15:30:00', $rows[0]['ultima_compra']);
    }

    public function test_clientsByPeriod_excludes_purchases_outside_range(): void
    {
        $this->seedVenta(1, 1, 300.00, '2025-05-15 10:00:00'); // fuera de rango

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(0, $rows);
    }

    public function test_clientsByPeriod_client_without_purchases_not_included(): void
    {
        // Solo Alpha tiene ventas en el período; Beta y Gamma no
        $this->seedVenta(1, 1, 200.00, '2025-06-10 10:00:00');

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(1, $rows);
        $this->assertSame('Cliente Alpha', $rows[0]['cliente']);
    }

    public function test_clientsByPeriod_returns_empty_when_no_data(): void
    {
        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(0, $rows);
    }

    public function test_clientsByPeriod_returns_correct_nit_ci_and_email(): void
    {
        $this->seedVenta(1, 2, 100.00, '2025-06-10 10:00:00');

        $rows = $this->report->clientsByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame('22222222', $rows[0]['nit_ci']);
        $this->assertSame('beta@test.com', $rows[0]['email']);
    }
}

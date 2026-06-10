<?php

namespace Tests\Integration\Models;

use App\Models\Report;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class ReportPurchasesTest extends TestCase
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
        $this->pdo->exec("INSERT INTO tb_proveedores (nombre_proveedor, celular, empresa, direccion)
                          VALUES ('Distribuidora ABC', '70000001', 'ABC S.R.L.', 'Calle 1'),
                                 ('Proveedor XYZ',    '70000002', 'XYZ S.A.',   'Calle 2')");

        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('Admin Test', 'admin@test.com', 'hash', 1)");
    }

    private function seedPurchase(
        int    $nroCompra,
        float  $precio,
        int    $cantidad,
        string $fechaCompra,
        string $fyhCreacion,
        int    $idProveedor = 1,
        int    $idUsuario   = 1
    ): void {
        $this->pdo->exec("INSERT INTO tb_compras
                          (nro_compra, id_producto, fecha_compra, id_proveedor, comprobante,
                           id_usuario, precio_compra, cantidad, fyh_creacion)
                          VALUES ({$nroCompra}, 1, '{$fechaCompra}', {$idProveedor}, 'FAC-{$nroCompra}',
                                  {$idUsuario}, {$precio}, {$cantidad}, '{$fyhCreacion}')");
    }

    // ── purchasesByPeriod ─────────────────────────────────────────────────────

    public function test_purchasesByPeriod_returns_purchases_within_range(): void
    {
        $this->seedPurchase(1, 100.00, 2, '2025-06-10', '2025-06-10 10:00:00');
        $this->seedPurchase(2, 200.00, 1, '2025-06-20', '2025-06-20 12:00:00');
        $this->seedPurchase(3, 300.00, 3, '2025-07-01', '2025-07-01 09:00:00'); // fuera

        $rows = $this->report->purchasesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(2, $rows);
    }

    public function test_purchasesByPeriod_returns_empty_when_no_purchases_in_range(): void
    {
        $this->seedPurchase(1, 100.00, 1, '2025-05-01', '2025-05-01 10:00:00');

        $rows = $this->report->purchasesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertCount(0, $rows);
    }

    public function test_purchasesByPeriod_includes_supplier_and_user_names(): void
    {
        $this->seedPurchase(1, 50.00, 2, '2025-06-10', '2025-06-10 10:00:00', 1, 1);

        $rows = $this->report->purchasesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame('Distribuidora ABC', $rows[0]['proveedor']);
        $this->assertSame('Admin Test', $rows[0]['registrado_por']);
    }

    public function test_purchasesByPeriod_calculates_monto_total_correctly(): void
    {
        $this->seedPurchase(1, 25.50, 4, '2025-06-10', '2025-06-10 10:00:00');

        $rows = $this->report->purchasesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertEqualsWithDelta(102.00, (float)$rows[0]['monto_total'], 0.01);
    }

    public function test_purchasesByPeriod_ordered_by_date_descending(): void
    {
        $this->seedPurchase(1, 100.00, 1, '2025-06-05', '2025-06-05 10:00:00');
        $this->seedPurchase(2, 200.00, 1, '2025-06-20', '2025-06-20 10:00:00');

        $rows = $this->report->purchasesByPeriod('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(2, (int)$rows[0]['nro_compra']);
        $this->assertSame(1, (int)$rows[1]['nro_compra']);
    }

    // ── purchasesTotals ───────────────────────────────────────────────────────

    public function test_purchasesTotals_sums_correctly(): void
    {
        $this->seedPurchase(1, 100.00, 2, '2025-06-10', '2025-06-10 10:00:00'); // 200
        $this->seedPurchase(2, 50.00, 3, '2025-06-15', '2025-06-15 12:00:00');  // 150

        $totals = $this->report->purchasesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(2, (int)$totals['num_compras']);
        $this->assertEqualsWithDelta(350.00, (float)$totals['total_egresos'], 0.01);
    }

    public function test_purchasesTotals_returns_zeros_when_no_purchases(): void
    {
        $totals = $this->report->purchasesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(0, (int)$totals['num_compras']);
        $this->assertEqualsWithDelta(0.0, (float)$totals['total_egresos'], 0.01);
    }

    public function test_purchasesTotals_excludes_purchases_outside_range(): void
    {
        $this->seedPurchase(1, 500.00, 1, '2025-05-01', '2025-05-01 10:00:00'); // fuera
        $this->seedPurchase(2, 100.00, 2, '2025-06-10', '2025-06-10 10:00:00'); // dentro → 200

        $totals = $this->report->purchasesTotals('2025-06-01 00:00:00', '2025-06-30 23:59:59');

        $this->assertSame(1, (int)$totals['num_compras']);
        $this->assertEqualsWithDelta(200.00, (float)$totals['total_egresos'], 0.01);
    }
}

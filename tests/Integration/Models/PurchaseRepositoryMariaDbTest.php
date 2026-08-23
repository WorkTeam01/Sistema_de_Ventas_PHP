<?php

namespace Tests\Integration\Models;

use App\Models\Purchase;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Cubre métodos de Purchase con funciones de fecha específicas de MySQL
 * (CURDATE(), YEAR(), MONTH(), DATE_FORMAT()) que SQLite in-memory no soporta.
 * Ver PurchaseRepositoryTest para el resto de la cobertura (SQLite).
 */
final class PurchaseRepositoryMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private Purchase $purchase;
    private static int $nroCompra = 1;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->purchase = new Purchase();
        self::$nroCompra = 1;

        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES
            ('Admin Uno', 'a1@test.com', 'hash', 1),
            ('Admin Dos', 'a2@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_proveedores (nombre_proveedor, celular, empresa, direccion)
            VALUES ('Proveedor A', '70000001', 'Empresa A', 'Calle 1')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, precio_compra, precio_venta, stock, stock_minimo, stock_maximo, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P001', 'Producto A', 10.00, 15.00, 50, 5, 100, '2026-01-01', 1, 1)");
    }

    /**
     * $sqlDate es una expresión SQL (CURDATE(), CURDATE() - INTERVAL 1 MONTH, ...)
     * evaluada por el propio MariaDB, no una fecha calculada con date()/strtotime()
     * de PHP — el reloj/timezone de PHP y el del servidor MariaDB pueden no coincidir.
     */
    private function createPurchase(float $precioCompra, int $cantidad, string $sqlDate, ?int $userId = null): void
    {
        $nroCompra = self::$nroCompra++;
        $this->pdo->exec("INSERT INTO tb_compras
            (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
            VALUES (1, $nroCompra, $sqlDate, 1, 'FAC-$nroCompra', " . ($userId ?? 1) . ", $precioCompra, $cantidad)");
    }

    public function test_totalCurrentMonth_sums_only_current_month_purchases(): void
    {
        $this->createPurchase(10.00, 5, 'CURDATE()');
        $this->createPurchase(5.00, 2, 'CURDATE() - INTERVAL 1 MONTH');

        $this->assertSame(50.00, $this->purchase->totalCurrentMonth());
    }

    public function test_totalPreviousMonth_sums_only_previous_month_purchases(): void
    {
        $this->createPurchase(10.00, 5, 'CURDATE()');
        $this->createPurchase(5.00, 2, 'CURDATE() - INTERVAL 1 MONTH');

        $this->assertSame(10.00, $this->purchase->totalPreviousMonth());
    }

    public function test_totalsByMonth_groups_by_month(): void
    {
        $this->createPurchase(10.00, 5, 'CURDATE()');
        $this->createPurchase(5.00, 2, 'CURDATE() - INTERVAL 1 MONTH');

        $result = $this->purchase->totalsByMonth(2);

        $this->assertCount(2, $result);
        $this->assertSame(10.00, (float)$result[0]['total']);
        $this->assertSame(50.00, (float)$result[1]['total']);
    }

    public function test_totalCurrentMonth_filters_by_userId(): void
    {
        $this->createPurchase(10.00, 5, 'CURDATE()', 1);
        $this->createPurchase(20.00, 1, 'CURDATE()', 2);

        $this->assertSame(50.00, $this->purchase->totalCurrentMonth(1));
    }
}

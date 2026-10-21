<?php

namespace Tests\Integration\Models;

use App\Models\InventoryMovement;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class InventoryMovementTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private InventoryMovement $movements;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->movements = new InventoryMovement();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, unidad_medida, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('M-1', 'Cable', 'metro', 12, 1, 2, '2026-01-01', 1, 1)");
    }

    public function test_record_preserves_a_product_snapshot_for_each_correction(): void
    {
        $this->movements->record(1, 5, 'Compra', 'FAC-1', 1, 'Admin', '2026-02-01 10:00:00');
        $this->pdo->exec("UPDATE tb_almacen SET nombre = 'Cable nuevo', unidad_medida = 'rollo' WHERE id_producto = 1");
        $this->movements->record(1, -2, 'Corrección de compra', 'FAC-1', 1, 'Admin', '2026-02-02 10:00:00');

        $rows = $this->pdo->query(
            'SELECT nombre_producto, unidad_medida, fecha, cantidad, usuario_nombre
             FROM tb_movimientos_inventario ORDER BY id_movimiento'
        )->fetchAll();

        $this->assertSame('Cable', $rows[0]['nombre_producto']);
        $this->assertSame('metro', $rows[0]['unidad_medida']);
        $this->assertSame('2026-02-01 10:00:00', $rows[0]['fecha']);
        $this->assertSame(5, (int)$rows[0]['cantidad']);
        $this->assertSame('Cable nuevo', $rows[1]['nombre_producto']);
        $this->assertSame('rollo', $rows[1]['unidad_medida']);
        $this->assertSame(-2, (int)$rows[1]['cantidad']);
        $this->assertSame('Admin', $rows[1]['usuario_nombre']);
    }
}

<?php

namespace Tests\Integration\Models;

use PDO;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

final class InventoryMovementMigrationMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ([
            'tb_documentos_comerciales_historial',
            'tb_documento_comercial_items',
            'tb_documentos_comerciales',
            'tb_movimientos_inventario',
        ] as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $this->pdo->exec('ALTER TABLE tb_ventas DROP COLUMN tipo_documento');
        $this->pdo->exec('ALTER TABLE tb_almacen DROP COLUMN unidad_medida');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function test_migrations_import_movements_and_reconcile_legacy_stock(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_proveedores
            (nombre_proveedor, celular, empresa, direccion)
            VALUES ('Proveedor', '123', 'Ferretería', 'Local')");
        $this->pdo->exec("INSERT INTO tb_clientes
            (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Cliente', '12345678', '123', 'cliente@test.com')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P-1', 'Producto con movimientos', 'Test', 30, 1, 2, '2026-01-01', 1, 1),
                   ('P-2', 'Producto sin movimientos', 'Test', 7, 1, 2, '2026-01-01', 1, 1)");
        $this->pdo->exec("INSERT INTO tb_ajustes_stock
            (id_producto, tipo, cantidad, stock_anterior, stock_posterior, motivo, id_usuario, usuario_nombre, fyh_creacion)
            VALUES (1, 'entrada', 5, 0, 5, 'Entrada', 1, 'Admin', '2026-02-01 10:00:00'),
                   (1, 'salida', 2, 5, 3, 'Salida', 1, 'Admin', '2026-02-02 10:00:00')");
        $this->pdo->exec("INSERT INTO tb_compras
            (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
            VALUES (1, 1, '2026-02-03', 1, 'FAC-1', 1, 1, 4)");
        $this->pdo->exec("INSERT INTO tb_ventas
            (nro_venta, id_cliente, id_usuario, total_pagado, fyh_creacion)
            VALUES (1, 1, 1, 16, '2026-02-04 10:00:00')");
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario)
            VALUES (1, 1, 8, 2)");
        $this->pdo->exec("INSERT INTO tb_devoluciones
            (nro_devolucion, id_venta, id_usuario, motivo, monto, fyh_creacion)
            VALUES (1, 1, 1, 'Devolución test', 2, '2026-02-05 10:00:00')");
        $this->pdo->exec("INSERT INTO tb_devolucion_items
            (id_devolucion, id_producto, cantidad, precio_unitario)
            VALUES (1, 1, 1, 2)");

        $this->applyMigration('016_documentos_comerciales.sql');
        $this->applyMigration('017_libro_movimientos_inventario.sql');

        $rows = $this->pdo->query(
            'SELECT p.id_producto, p.stock, SUM(m.cantidad) AS saldo_libro,
                    SUM(CASE WHEN m.tipo = \'Saldo inicial migrado\' THEN m.cantidad ELSE 0 END) AS saldo_inicial
             FROM tb_almacen p
             INNER JOIN tb_movimientos_inventario m ON m.id_producto = p.id_producto
             GROUP BY p.id_producto, p.stock ORDER BY p.id_producto'
        )->fetchAll(PDO::FETCH_ASSOC);

        self::assertCount(2, $rows);
        self::assertSame(30, (int)$rows[0]['stock']);
        self::assertSame(30, (int)$rows[0]['saldo_libro']);
        self::assertSame(30, (int)$rows[0]['saldo_inicial']);
        self::assertSame(7, (int)$rows[1]['stock']);
        self::assertSame(7, (int)$rows[1]['saldo_libro']);
        self::assertSame(7, (int)$rows[1]['saldo_inicial']);
        self::assertSame(
            7,
            (int)$this->pdo->query('SELECT COUNT(*) FROM tb_movimientos_inventario')->fetchColumn()
        );
        self::assertSame(
            1,
            (int)$this->pdo->query(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_documentos_comerciales'"
            )->fetchColumn()
        );
        self::assertSame(
            1,
            (int)$this->pdo->query(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_almacen'
                   AND COLUMN_NAME = 'unidad_medida'"
            )->fetchColumn()
        );
    }

    private function applyMigration(string $file): void
    {
        $sql = file_get_contents(__DIR__ . '/../../../database/migrations/' . $file);
        self::assertNotFalse($sql);
        $this->pdo->exec($sql);
    }
}

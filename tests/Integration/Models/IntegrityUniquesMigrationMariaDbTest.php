<?php

namespace Tests\Integration\Models;

use PDOException;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

final class IntegrityUniquesMigrationMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private const MIGRATION = __DIR__ . '/../../../database/migrations/012_integridad_unicos.sql';

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Ana', 'ana@test.com', 'x', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec("INSERT INTO tb_proveedores (nombre_proveedor, celular, empresa, direccion) VALUES ('Prov', '1', 'E', 'D')");
        $this->pdo->exec(
            "INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
             VALUES ('P1', 'Prod', 'd', 10, 1, 2, CURDATE(), 1, 1)"
        );
    }

    /** Deja la BD como en 1.18.3: FK venta→carrito y sin los UNIQUE. */
    private function legacyState(string $fkName = 'tb_ventas_ibfk_2'): void
    {
        $this->pdo->exec('ALTER TABLE tb_ventas DROP INDEX uq_venta_nro');
        $this->pdo->exec('ALTER TABLE tb_compras DROP INDEX uq_compra_nro');
        $this->pdo->exec(
            "ALTER TABLE tb_ventas ADD CONSTRAINT `{$fkName}` FOREIGN KEY (nro_venta) REFERENCES tb_carrito (nro_venta)"
        );
    }

    private function runMigration(): void
    {
        $this->pdo->exec(file_get_contents(self::MIGRATION));
    }

    private function cart(int $nro): void
    {
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES ({$nro}, 1, 1)");
    }

    private function sale(int $nro): void
    {
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES ({$nro}, 1, 2)");
    }

    private function purchase(int $nro): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_compras (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
             VALUES (1, {$nro}, CURDATE(), 1, 'c', 1, 1, 1)"
        );
    }

    private function indexIsUnique(string $table, string $index): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT NON_UNIQUE FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1'
        );
        $stmt->execute([$table, $index]);
        $v = $stmt->fetchColumn();
        return $v !== false && (int)$v === 0;
    }

    private function fkToCartCount(): int
    {
        return (int)$this->pdo->query(
            "SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_ventas' AND REFERENCED_TABLE_NAME = 'tb_carrito'"
        )->fetchColumn();
    }

    private function migrationError(): string
    {
        try {
            $this->runMigration();
        } catch (PDOException $e) {
            return $e->getMessage();
        }
        $this->fail('La migración debía abortar');
    }

    public function testSchemaSqlHasUniquesAndNoInvertedForeignKey(): void
    {
        $this->assertTrue($this->indexIsUnique('tb_ventas', 'uq_venta_nro'));
        $this->assertTrue($this->indexIsUnique('tb_compras', 'uq_compra_nro'));
        $this->assertSame(0, $this->fkToCartCount());
    }

    public function testMigrationOnCleanLegacyDatabaseRunsTwice(): void
    {
        $this->cart(5);
        $this->sale(5);
        $this->purchase(7);
        $this->legacyState();

        $this->runMigration();
        $this->runMigration();

        $this->assertTrue($this->indexIsUnique('tb_ventas', 'uq_venta_nro'));
        $this->assertTrue($this->indexIsUnique('tb_compras', 'uq_compra_nro'));
        $this->assertSame(0, $this->fkToCartCount());
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_ventas')->fetchColumn());
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_compras')->fetchColumn());
    }

    public function testForeignKeyIsDroppedEvenWithAnotherName(): void
    {
        $this->legacyState('fk_nombre_distinto');

        $this->runMigration();

        $this->assertSame(0, $this->fkToCartCount());
    }

    public function testAbortsOnDuplicateNroVentaWithoutChangingAnything(): void
    {
        $this->cart(9);
        $this->legacyState();
        $this->sale(9);
        $this->sale(9);

        $msg = $this->migrationError();

        $this->assertStringContainsString('nro_venta repetido', $msg);
        $this->assertStringContainsString('9', $msg);
        $this->assertSame(1, $this->fkToCartCount());
        $this->assertFalse($this->indexIsUnique('tb_ventas', 'uq_venta_nro'));
        $this->assertFalse($this->indexIsUnique('tb_compras', 'uq_compra_nro'));
        $this->assertSame(2, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_ventas')->fetchColumn());
    }

    public function testAbortsOnSaleWithoutCartLines(): void
    {
        $this->legacyState();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->sale(33);
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $msg = $this->migrationError();

        $this->assertStringContainsString('venta sin líneas', $msg);
        $this->assertStringContainsString('33', $msg);
        $this->assertSame(1, $this->fkToCartCount());
    }

    public function testAbortsOnDuplicateNroCompra(): void
    {
        $this->legacyState();
        $this->purchase(4);
        $this->purchase(4);

        $msg = $this->migrationError();

        $this->assertStringContainsString('nro_compra repetido', $msg);
        $this->assertStringContainsString('4', $msg);
        $this->assertFalse($this->indexIsUnique('tb_compras', 'uq_compra_nro'));
        $this->assertSame(1, $this->fkToCartCount());
    }
}

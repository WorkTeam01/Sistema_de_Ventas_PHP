<?php

namespace Tests\Integration\Models;

use PDOException;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Las migraciones 002, 003 y 006 se reescribieron sin `ADD COLUMN IF NOT EXISTS` (no existe en
 * MySQL 8.4) y la 014 convierte tb_almacen.imagen a VARCHAR(255). Los archivos originales de
 * 1.18.3 viven en tests/fixtures/migrations_1.18.3 como referencia de equivalencia.
 */
final class MigrationsPortabilityMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private const DIR = __DIR__ . '/../../../database/migrations/';
    private const ORIGINALS = __DIR__ . '/../../fixtures/migrations_1.18.3/';
    private const REWRITTEN = ['002_ventas_id_usuario.sql', '003_permisos_version.sql', '006_carrito_precio_unitario.sql'];

    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    /** Estado previo a 002/003/006: sin las tres columnas (ni la FK de ventas.id_usuario). */
    private function preMigrationState(): void
    {
        $this->pdo->exec('ALTER TABLE tb_ventas DROP FOREIGN KEY tb_ventas_ibfk_3');
        $this->pdo->exec('ALTER TABLE tb_ventas DROP COLUMN id_usuario');
        $this->pdo->exec('ALTER TABLE tb_roles DROP COLUMN permisos_version');
        $this->pdo->exec('ALTER TABLE tb_carrito DROP COLUMN precio_unitario');
    }

    private function apply(string $dir): void
    {
        foreach (self::REWRITTEN as $file) {
            $this->pdo->exec(file_get_contents($dir . $file));
        }
    }

    /** @return array<string, mixed> */
    private function structure(): array
    {
        $cols = $this->pdo->query(
            "SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             ORDER BY TABLE_NAME, ORDINAL_POSITION"
        )->fetchAll();
        $fks = $this->pdo->query(
            "SELECT k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME,
                    r.UPDATE_RULE, r.DELETE_RULE
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS r
               ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
             WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = 'tb_ventas'
             ORDER BY k.COLUMN_NAME"
        )->fetchAll();
        return ['columns' => $cols, 'ventas_fks' => $fks];
    }

    public function testNoMigrationUsesAddColumnIfNotExists(): void
    {
        foreach (glob(self::DIR . '*.sql') as $file) {
            $this->assertStringNotContainsStringIgnoringCase('ADD COLUMN IF NOT EXISTS', file_get_contents($file), basename($file));
        }
    }

    public function testRewrittenMigrationsProduceTheSameStructureAsTheOriginals(): void
    {
        $this->preMigrationState();
        $this->apply(self::ORIGINALS);
        $expected = $this->structure();

        $this->preMigrationState();
        $this->apply(self::DIR);

        $this->assertSame($expected, $this->structure());
    }

    public function testRewrittenMigrationsRunTwiceOnPreMigrationDatabase(): void
    {
        $this->preMigrationState();
        $this->apply(self::DIR);
        $this->apply(self::DIR);

        $this->assertSame(3, (int)$this->pdo->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND ((TABLE_NAME = 'tb_ventas' AND COLUMN_NAME = 'id_usuario')
               OR (TABLE_NAME = 'tb_roles' AND COLUMN_NAME = 'permisos_version')
               OR (TABLE_NAME = 'tb_carrito' AND COLUMN_NAME = 'precio_unitario'))"
        )->fetchColumn());
    }

    public function testRewrittenMigrationsAreNoOpOnAlreadyMigratedDatabase(): void
    {
        $before = $this->structure();

        $this->apply(self::DIR);

        $this->assertSame($before, $this->structure());
    }

    public function testBackfillOf006KeepsHistoricalPricesOnRerun(): void
    {
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Ana', 'a@t.com', 'x', 1)");
        $this->pdo->exec(
            "INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
             VALUES ('P1', 'Prod', 'd', 10, 1, 9, CURDATE(), 1, 1)"
        );
        $this->pdo->exec('INSERT INTO tb_carrito (nro_venta, id_producto, cantidad, precio_unitario) VALUES (7, 1, 1, 3.50)');
        $this->pdo->exec('INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES (7, 1, 3.50)');

        $this->pdo->exec(file_get_contents(self::DIR . '006_carrito_precio_unitario.sql'));

        $this->assertSame('3.50', $this->pdo->query('SELECT precio_unitario FROM tb_carrito')->fetchColumn());
    }

    public function testSchemaSqlHasNoTextColumnWithLiteralDefault(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/\btext\s+NOT NULL\s+DEFAULT/i',
            file_get_contents(__DIR__ . '/../../../database/schema.sql')
        );
    }

    private function runMigration014(): void
    {
        $this->pdo->exec(file_get_contents(self::DIR . '014_portabilidad_mysql84.sql'));
    }

    private function imageColumnType(): string
    {
        return (string)$this->pdo->query(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_almacen' AND COLUMN_NAME = 'imagen'"
        )->fetchColumn();
    }

    private function seedProduct(string $code, string $image): int
    {
        $this->pdo->exec("INSERT IGNORE INTO tb_categorias (id_categoria, nombre_categoria) VALUES (1, 'Cat')");
        $this->pdo->exec("INSERT IGNORE INTO tb_roles (id_rol, rol) VALUES (1, 'Administrador')");
        $this->pdo->exec("INSERT IGNORE INTO tb_usuarios (id_usuario, nombres, email, password_user, id_rol) VALUES (1, 'Ana', 'a@t.com', 'x', 1)");
        $stmt = $this->pdo->prepare(
            "INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, imagen, id_usuario, id_categoria)
             VALUES (?, 'Prod', 'd', 10, 1, 2, CURDATE(), ?, 1, 1)"
        );
        $stmt->execute([$code, $image]);
        return (int)$this->pdo->lastInsertId();
    }

    private function legacyImageColumn(): void
    {
        $this->pdo->exec("ALTER TABLE tb_almacen MODIFY imagen text NOT NULL DEFAULT 'producto_default.png'");
    }

    public function testSchemaSqlDeclaresImageAsVarchar255(): void
    {
        $this->assertSame('varchar(255)', $this->imageColumnType());
    }

    public function testMigration014ConvertsImageAndRunsTwice(): void
    {
        $this->legacyImageColumn();
        $id = $this->seedProduct('P1', 'products/foto.png');

        $this->runMigration014();
        $this->runMigration014();

        $this->assertSame('varchar(255)', $this->imageColumnType());
        $this->assertSame('products/foto.png', $this->pdo->query("SELECT imagen FROM tb_almacen WHERE id_producto = {$id}")->fetchColumn());
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria) VALUES ('P2', 'N', 'd', 1, 1, 1, CURDATE(), 1, 1)");
        $this->assertSame('producto_default.png', $this->pdo->query("SELECT imagen FROM tb_almacen WHERE codigo = 'P2'")->fetchColumn());
    }

    public function testMigration014AbortsWithoutChangingAnythingWhenAnImageIsTooLong(): void
    {
        $this->legacyImageColumn();
        $ok = $this->seedProduct('P1', 'corta.png');
        $long = $this->seedProduct('P2', str_repeat('x', 300));

        try {
            $this->runMigration014();
            $this->fail('La migración debía abortar');
        } catch (PDOException $e) {
            $this->assertStringContainsString('imagen', $e->getMessage());
            $this->assertStringContainsString((string)$long, $e->getMessage());
        }

        $this->assertSame('text', $this->imageColumnType());
        $this->assertSame(300, (int)$this->pdo->query("SELECT CHAR_LENGTH(imagen) FROM tb_almacen WHERE id_producto = {$long}")->fetchColumn());
        $this->assertSame('corta.png', $this->pdo->query("SELECT imagen FROM tb_almacen WHERE id_producto = {$ok}")->fetchColumn());
    }
}

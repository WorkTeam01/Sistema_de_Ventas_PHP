<?php

namespace Tests\Integration\Models;

use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

final class PosReservationSchemaMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private const MIGRATION = __DIR__ . '/../../../database/migrations/011_pos_reservas.sql';

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
             VALUES ('Ana Pérez', 'ana@test.com', 'x', 1), ('Beto Ruiz', 'beto@test.com', 'x', 1)"
        );
    }

    private function runMigration(): void
    {
        $this->pdo->exec(file_get_contents(self::MIGRATION));
    }

    private function seedSales(): void
    {
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec(
            "INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
             VALUES ('P1', 'Prod', 'd', 10, 1, 2, CURDATE(), 1, 1)"
        );
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (41, 1, 1), (57, 1, 1)");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado) VALUES (41, NULL, 1, 2)");
    }

    private function rebuildEmptyTable(): void
    {
        $this->pdo->exec('DROP TABLE tb_pos_reservas');
    }

    public function testMigrationRunsTwiceWithoutErrorOrDuplicateRows(): void
    {
        $this->seedSales();
        $this->rebuildEmptyTable();

        $this->runMigration();
        $this->runMigration();

        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pos_reservas')->fetchColumn());
    }

    public function testSeedRowIsReleasedAboveMaxNroVentaOfSalesAndCart(): void
    {
        $this->seedSales();
        $this->rebuildEmptyTable();
        $this->runMigration();

        $row = $this->pdo->query('SELECT id_reserva, id_usuario, fyh_liberacion FROM tb_pos_reservas')->fetch();
        $this->assertSame(57, (int)$row['id_reserva']);
        $this->assertNull($row['id_usuario']);
        $this->assertNotNull($row['fyh_liberacion']);

        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES (2)");
        $this->assertGreaterThan(57, (int)$this->pdo->lastInsertId());
    }

    public function testEmptyDatabaseGetsTableWithoutSeedRow(): void
    {
        $this->rebuildEmptyTable();
        $this->runMigration();

        $this->assertSame(0, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pos_reservas')->fetchColumn());
    }

    public function testMigrationDoesNotReseedWhenReservationsAlreadyCoverEverySale(): void
    {
        $this->seedSales();
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_reserva, id_usuario) VALUES (57, 2)");
        $this->runMigration();

        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pos_reservas')->fetchColumn());
    }

    public function testMigrationRaisesSequenceAfterReturningToOldVersionSoldAboveIt(): void
    {
        $this->seedSales();
        $this->rebuildEmptyTable();
        $this->runMigration();

        // 1.18.3 numera con MAX(nro_venta)+1 e ignora tb_pos_reservas: vende el 58 y el 59.
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (59, 1, 1)");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado) VALUES (59, NULL, 1, 2)");
        $this->runMigration();

        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES (2)");
        $this->assertSame(60, (int)$this->pdo->lastInsertId());
    }

    public function testDeletingAUserWhoOpenedThePosKeepsTheReservationWithoutOwner(): void
    {
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES (2)");

        $this->pdo->exec('DELETE FROM tb_usuarios WHERE id_usuario = 2');

        $row = $this->pdo->query('SELECT id_usuario FROM tb_pos_reservas')->fetch();
        $this->assertNull($row['id_usuario']);
    }

    public function testMigrationReplacesTheOldForeignKeyOnAnExistingTable(): void
    {
        $this->rebuildEmptyTable();
        $this->pdo->exec(
            'CREATE TABLE tb_pos_reservas (
               id_reserva INT NOT NULL AUTO_INCREMENT, id_usuario INT NOT NULL,
               fyh_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, fyh_liberacion DATETIME DEFAULT NULL,
               PRIMARY KEY (id_reserva), KEY idx_reserva_usuario (id_usuario, fyh_liberacion),
               CONSTRAINT fk_reserva_usuario FOREIGN KEY (id_usuario) REFERENCES tb_usuarios (id_usuario)
             ) ENGINE=InnoDB'
        );
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES (2)");

        $this->runMigration();
        $this->pdo->exec('DELETE FROM tb_usuarios WHERE id_usuario = 2');

        $this->assertNull($this->pdo->query('SELECT id_usuario FROM tb_pos_reservas')->fetchColumn());
        $this->assertSame('SET NULL', $this->pdo->query(
            "SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_pos_reservas'"
        )->fetchColumn());
    }

    public function testSchemaSqlDeclaresTheTable(): void
    {
        $cols = $this->pdo->query('SHOW COLUMNS FROM tb_pos_reservas')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame(['id_reserva', 'id_usuario', 'fyh_creacion', 'fyh_liberacion'], $cols);
    }
}

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
        $this->assertSame(1, (int)$row['id_usuario']);
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

    public function testMigrationDoesNotReseedWhenTableAlreadyHasRows(): void
    {
        $this->seedSales();
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES (2)");
        $this->runMigration();

        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pos_reservas')->fetchColumn());
    }

    public function testSchemaSqlDeclaresTheTable(): void
    {
        $cols = $this->pdo->query('SHOW COLUMNS FROM tb_pos_reservas')->fetchAll(\PDO::FETCH_COLUMN);
        $this->assertSame(['id_reserva', 'id_usuario', 'fyh_creacion', 'fyh_liberacion'], $cols);
    }
}

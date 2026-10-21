<?php

namespace Tests\Integration\Models;

use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Las migraciones aplicadas en orden sobre el estado anterior a la 001 deben dejar la misma
 * estructura (tablas, columnas, índices, claves foráneas) que database/schema.sql.
 *
 * Las migraciones no recrean las tablas base (ventas, almacén, usuarios...), así que el punto
 * de partida es schema.sql con lo que las migraciones agregan o cambian revertido a mano.
 */
final class MigrationsMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private const DIR = __DIR__ . '/../../../database/migrations/';

    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    /** Estructura de BD previa a la migración 001, con los datos que las migraciones sembrarán. */
    private function revertToBaseline(): void
    {
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ([
            'tb_documentos_comerciales_historial', 'tb_documento_comercial_items',
            'tb_documentos_comerciales', 'tb_movimientos_inventario',
            'tb_rol_permiso', 'tb_permisos', 'tb_devolucion_items', 'tb_devoluciones', 'tb_pagos',
            'tb_metodos_pago', 'tb_pos_reservas', 'tb_intentos_auth', 'tb_sesiones_recordadas',
        ] as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        $this->pdo->exec('ALTER TABLE tb_ventas DROP COLUMN tipo_documento');
        $this->pdo->exec('ALTER TABLE tb_almacen DROP COLUMN unidad_medida');
        $this->pdo->exec('ALTER TABLE tb_ventas DROP FOREIGN KEY tb_ventas_ibfk_3');
        $this->pdo->exec('ALTER TABLE tb_ventas DROP COLUMN id_usuario');
        $this->pdo->exec('ALTER TABLE tb_ventas DROP INDEX uq_venta_nro');
        $this->pdo->exec('ALTER TABLE tb_ventas ADD KEY nro_venta (nro_venta)');
        $this->pdo->exec('ALTER TABLE tb_ventas MODIFY id_cliente INT(11) NOT NULL');
        $this->pdo->exec(
            'ALTER TABLE tb_ventas ADD CONSTRAINT tb_ventas_ibfk_2 FOREIGN KEY (nro_venta) REFERENCES tb_carrito (nro_venta)'
        );
        $this->pdo->exec('ALTER TABLE tb_compras DROP INDEX uq_compra_nro');
        $this->pdo->exec('ALTER TABLE tb_carrito DROP COLUMN precio_unitario');
        $this->pdo->exec('ALTER TABLE tb_roles DROP COLUMN permisos_version');
        $this->pdo->exec("ALTER TABLE tb_almacen MODIFY imagen text NOT NULL DEFAULT 'producto_default.png'");
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function structure(): array
    {
        $q = fn(string $sql) => $this->pdo->query($sql)->fetchAll();
        return [
            'tables' => $q(
                "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"
            ),
            'columns' => $q(
                "SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
                 ORDER BY TABLE_NAME, ORDINAL_POSITION"
            ),
            'indexes' => $this->indexSignatures(),
            'foreign_keys' => $q(
                "SELECT k.TABLE_NAME, k.CONSTRAINT_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME,
                        k.REFERENCED_COLUMN_NAME, r.UPDATE_RULE, r.DELETE_RULE
                 FROM information_schema.KEY_COLUMN_USAGE k
                 JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                   ON r.CONSTRAINT_SCHEMA = k.TABLE_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                 WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL
                 ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION"
            ),
        ];
    }

    /**
     * Índices por columnas y unicidad, sin el nombre (una BD migrada conserva los nombres
     * autogenerados por las FK) y sin los no únicos que son prefijo de otro índice (redundantes).
     *
     * @return list<string>
     */
    private function indexSignatures(): array
    {
        $rows = $this->pdo->query(
            "SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols
             FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()
             GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE"
        )->fetchAll();

        $signatures = [];
        foreach ($rows as $row) {
            $redundant = false;
            if ((int)$row['NON_UNIQUE'] === 1) {
                foreach ($rows as $other) {
                    if ($other['TABLE_NAME'] === $row['TABLE_NAME'] && $other['INDEX_NAME'] !== $row['INDEX_NAME']
                        && str_starts_with($other['cols'] . ',', $row['cols'] . ',')) {
                        $redundant = true;
                        break;
                    }
                }
            }
            if (!$redundant) {
                $signatures[] = "{$row['TABLE_NAME']}({$row['cols']})" . ((int)$row['NON_UNIQUE'] === 0 ? ' UNIQUE' : '');
            }
        }
        sort($signatures);
        return $signatures;
    }

    private function applyAllMigrations(): void
    {
        $files = glob(self::DIR . '*.sql');
        sort($files);
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $this->pdo->exec(file_get_contents($file));
        }
    }

    public function testMigrationsInOrderMatchSchemaSql(): void
    {
        $expected = $this->structure();
        $this->revertToBaseline();
        $this->assertNotSame($expected, $this->structure(), 'El estado base debe diferir de schema.sql');

        $this->applyAllMigrations();

        $actual = $this->structure();
        foreach ($expected as $kind => $rows) {
            $this->assertSame($rows, $actual[$kind], "Diferencias en {$kind}");
        }
    }

    public function testSeederLeavesPosReservationAboveEverySeededSaleNumber(): void
    {
        $this->pdo->exec(file_get_contents(__DIR__ . '/../../../database/seeder.sql'));

        $maxSale = (int)$this->pdo->query('SELECT MAX(nro_venta) FROM tb_ventas')->fetchColumn();
        $maxCart = (int)$this->pdo->query('SELECT MAX(nro_venta) FROM tb_carrito')->fetchColumn();
        $this->assertGreaterThan(0, $maxSale);

        $this->pdo->exec('INSERT INTO tb_pos_reservas (id_usuario) VALUES (2)');
        $this->assertGreaterThan(max($maxSale, $maxCart), (int)$this->pdo->lastInsertId());
    }
}

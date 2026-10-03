<?php

namespace Tests\Integration\Models;

use PDOException;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class SqliteForeignKeysTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> "tabla.columna->referenciada.columna" declaradas en schema.sql */
    private function foreignKeysInSchemaSql(): array
    {
        $sql = file_get_contents(__DIR__ . '/../../../database/schema.sql');
        $found = [];
        // Tanto las inline de CREATE TABLE como las de ALTER TABLE ... ADD CONSTRAINT.
        preg_match_all('/(?:CREATE TABLE IF NOT EXISTS|ALTER TABLE)\s+`(\w+)`(.*?);/s', $sql, $statements, PREG_SET_ORDER);
        foreach ($statements as [, $table, $body]) {
            preg_match_all('/FOREIGN KEY\s*\(`(\w+)`\)\s*REFERENCES\s*`(\w+)`\s*\(`(\w+)`\)/', $body, $fks, PREG_SET_ORDER);
            foreach ($fks as [, $col, $refTable, $refCol]) {
                $found[] = "{$table}.{$col}->{$refTable}.{$refCol}";
            }
        }
        sort($found);
        return $found;
    }

    /** @return list<string> */
    private function foreignKeysInFixture(): array
    {
        $found = [];
        $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name LIKE 'tb\\_%' ESCAPE '\\'")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            foreach ($this->pdo->query("PRAGMA foreign_key_list({$table})")->fetchAll() as $fk) {
                $found[] = "{$table}.{$fk['from']}->{$fk['table']}.{$fk['to']}";
            }
        }
        sort($found);
        return $found;
    }

    public function testFixtureDeclaresTheSameForeignKeysAsSchemaSql(): void
    {
        $expected = $this->foreignKeysInSchemaSql();
        $this->assertNotEmpty($expected);

        $this->assertSame($expected, $this->foreignKeysInFixture());
    }

    public function testForeignKeyEnforcementIsOn(): void
    {
        $this->assertSame(1, (int)$this->pdo->query('PRAGMA foreign_keys')->fetchColumn());
    }

    public function testSaleWithUnknownClientIsRejected(): void
    {
        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/FOREIGN KEY constraint failed/');

        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado) VALUES (1, 999, NULL, 5)");
    }
}

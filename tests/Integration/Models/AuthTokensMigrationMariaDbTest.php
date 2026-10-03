<?php

namespace Tests\Integration\Models;

use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

final class AuthTokensMigrationMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private const MIGRATION = __DIR__ . '/../../../database/migrations/013_tokens_y_auth.sql';

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol, reset_token, reset_token_expiracion, remember_token, remember_token_expiry)
             VALUES ('Ana', 'ana@test.com', 'x', 1, 'abc', NOW() + INTERVAL 1 HOUR, 'def', NOW() + INTERVAL 1 DAY)"
        );
    }

    /** Deja la BD como en 1.18.3: sin columna ni tablas nuevas. */
    private function legacyState(): void
    {
        $this->pdo->exec('ALTER TABLE tb_devoluciones DROP COLUMN token_formulario');
        $this->pdo->exec('DROP TABLE tb_intentos_auth');
        $this->pdo->exec('DROP TABLE tb_sesiones_recordadas');
    }

    private function runMigration(): void
    {
        $this->pdo->exec(file_get_contents(self::MIGRATION));
    }

    private function exists(string $table): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() === 1;
    }

    private function tokenColumnIsUnique(): bool
    {
        $v = $this->pdo->query(
            "SELECT NON_UNIQUE FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_devoluciones' AND COLUMN_NAME = 'token_formulario' LIMIT 1"
        )->fetchColumn();
        return $v !== false && (int)$v === 0;
    }

    public function testSchemaSqlDeclaresTheNewStructures(): void
    {
        $this->assertTrue($this->exists('tb_intentos_auth'));
        $this->assertTrue($this->exists('tb_sesiones_recordadas'));
        $this->assertTrue($this->tokenColumnIsUnique());
    }

    public function testMigrationRunsTwiceAndNullsOldTokens(): void
    {
        $this->legacyState();

        $this->runMigration();
        $this->runMigration();

        $this->assertTrue($this->exists('tb_intentos_auth'));
        $this->assertTrue($this->exists('tb_sesiones_recordadas'));
        $this->assertTrue($this->tokenColumnIsUnique());
        $u = $this->pdo->query('SELECT reset_token, reset_token_expiracion, remember_token, remember_token_expiry FROM tb_usuarios')->fetch();
        $this->assertSame([null, null, null, null], array_values($u));
    }

    public function testRerunDoesNotInvalidateTokensIssuedAfterTheFirstRun(): void
    {
        $this->legacyState();
        $this->runMigration();
        $this->pdo->exec("UPDATE tb_usuarios SET reset_token = 'hash-nuevo', reset_token_expiracion = NOW() + INTERVAL 1 HOUR");

        $this->runMigration();

        $this->assertSame('hash-nuevo', $this->pdo->query('SELECT reset_token FROM tb_usuarios')->fetchColumn());
    }

    public function testExistingReturnsKeepNullTokenAndAllowSeveralNulls(): void
    {
        $this->legacyState();
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES (1, 1, 5)");
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, motivo, monto) VALUES (1, 1, 'm', 1), (2, 1, 'm', 1)");
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->runMigration();

        $this->assertSame(2, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_devoluciones WHERE token_formulario IS NULL')->fetchColumn());
    }
}

<?php

namespace Tests\Integration\Models;

use PDOException;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class AuthTokensFixtureTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('A', 'a@t.com', 'x', 1)");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente) VALUES ('C', '1', '1', 'c@t.com')");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado) VALUES (1, 1, 1, 5)");
    }

    public function testAuthAttemptsTableAcceptsRows(): void
    {
        $this->pdo->exec("INSERT INTO tb_intentos_auth (tipo, ip, correo, id_usuario) VALUES ('login', '127.0.0.1', 'a@t.com', NULL)");

        $this->assertGreaterThan(0, (int)$this->pdo->lastInsertId());
    }

    public function testRememberedSessionsTableEnforcesUniqueSelector(): void
    {
        $sql = "INSERT INTO tb_sesiones_recordadas (id_usuario, selector, token_hash, fyh_expira, ip)
                VALUES (1, 'sel', 'hash', '2030-01-01 00:00:00', '127.0.0.1')";
        $this->pdo->exec($sql);

        $this->expectException(PDOException::class);
        $this->pdo->exec($sql);
    }

    public function testReturnFormTokenIsUniqueButAllowsSeveralNulls(): void
    {
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, motivo, monto, token_formulario) VALUES (1, 1, 'm', 1, NULL), (2, 1, 'm', 1, NULL)");
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, motivo, monto, token_formulario) VALUES (3, 1, 'm', 1, 'tok')");

        $this->expectException(PDOException::class);
        $this->pdo->exec("INSERT INTO tb_devoluciones (nro_devolucion, id_venta, motivo, monto, token_formulario) VALUES (4, 1, 'm', 1, 'tok')");
    }
}

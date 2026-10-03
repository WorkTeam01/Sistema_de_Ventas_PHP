<?php

namespace Tests\Integration\Models;

use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class PosReservationFixtureTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('A', 'a@t.com', 'x', 1), ('B', 'b@t.com', 'x', 1)");
    }

    public function testAutoincrementContinuesAboveSeededId(): void
    {
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_reserva, id_usuario, fyh_liberacion) VALUES (57, 1, CURRENT_TIMESTAMP)");
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES (2)");

        $this->assertGreaterThan(57, (int)$this->pdo->lastInsertId());
    }

    public function testNewReservationIsOpenByDefault(): void
    {
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES (1)");

        $this->assertNull($this->pdo->query('SELECT fyh_liberacion FROM tb_pos_reservas')->fetchColumn());
    }
}

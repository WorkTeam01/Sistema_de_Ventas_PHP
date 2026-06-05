<?php

namespace Tests\Integration\Models;

use App\Models\Client;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class ClientRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Client $client;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->client = new Client();
    }

    // -------------------------------------------------------------------------
    // Helpers de seed mínimos
    // -------------------------------------------------------------------------

    private function createClient(string $email = 'cliente@example.com'): int
    {
        $this->pdo->exec("INSERT INTO tb_clientes
            (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Cliente Test', '12345678', '70000000', '$email')");
        return (int)$this->pdo->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // Persistencia básica
    // -------------------------------------------------------------------------

    public function test_createClient_persists_record(): void
    {
        $id = $this->createClient();

        $row = $this->client->find($id);
        $this->assertIsArray($row);
        $this->assertSame('Cliente Test', $row['nombre_cliente']);
        $this->assertSame('12345678', $row['nit_ci_cliente']);
        $this->assertSame('cliente@example.com', $row['email_cliente']);
    }

    public function test_updateClient_modifies_row(): void
    {
        $id = $this->createClient();

        $result = $this->client->update($id, [
            'nombre_cliente'  => 'Nombre Nuevo',
            'nit_ci_cliente'  => '99999999',
            'celular_cliente' => '71111111',
            'email_cliente'   => 'nuevo@example.com',
        ]);

        $this->assertTrue($result);
        $row = $this->client->find($id);
        $this->assertSame('Nombre Nuevo', $row['nombre_cliente']);
        $this->assertSame('nuevo@example.com', $row['email_cliente']);
    }

    public function test_all_returns_all_clients(): void
    {
        $this->createClient('a@example.com');
        $this->pdo->exec("INSERT INTO tb_clientes
            (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Cliente B', '87654321', '71111111', 'b@example.com')");

        $this->assertCount(2, $this->client->all());
    }

    public function test_delete_removes_record(): void
    {
        $id = $this->createClient();
        $this->client->delete($id);

        $this->assertFalse($this->client->find($id));
    }

    // -------------------------------------------------------------------------
    // isReferenced
    // -------------------------------------------------------------------------

    public function test_isReferenced_false_when_no_sales(): void
    {
        $id = $this->createClient();
        $this->assertFalse($this->client->isReferenced($id));
    }

    public function test_isReferenced_true_when_client_has_sales(): void
    {
        $id = $this->createClient();
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, 1, 1)");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, total_pagado)
            VALUES (1, $id, 100.00)");

        $this->assertTrue($this->client->isReferenced($id));
    }

    // -------------------------------------------------------------------------
    // nitCiExists / emailExists
    // -------------------------------------------------------------------------

    public function test_nitCiExists_returns_true_for_duplicate(): void
    {
        $this->createClient();

        $this->assertTrue($this->client->nitCiExists('12345678'));
    }

    public function test_nitCiExists_returns_false_for_new_value(): void
    {
        $this->assertFalse($this->client->nitCiExists('99999999'));
    }

    public function test_nitCiExists_excludes_own_id_on_edit(): void
    {
        $id = $this->createClient();

        // Editar el mismo cliente con su propio NIT no debe reportar duplicado
        $this->assertFalse($this->client->nitCiExists('12345678', $id));
    }

    public function test_emailExists_returns_true_for_duplicate(): void
    {
        $this->createClient('dup@example.com');

        $this->assertTrue($this->client->emailExists('dup@example.com'));
    }

    public function test_emailExists_returns_false_for_new_email(): void
    {
        $this->assertFalse($this->client->emailExists('nuevo@example.com'));
    }

    // -------------------------------------------------------------------------
    // Constraints únicos a nivel BD
    // -------------------------------------------------------------------------

    public function test_db_rejects_duplicate_nit_ci(): void
    {
        $this->createClient();
        $this->expectException(\PDOException::class);
        $this->pdo->exec("INSERT INTO tb_clientes
            (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Otro Cliente', '12345678', '79999999', 'otro@example.com')");
    }

    public function test_db_rejects_duplicate_email(): void
    {
        $this->createClient('dup@example.com');
        $this->expectException(\PDOException::class);
        $this->pdo->exec("INSERT INTO tb_clientes
            (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Otro Cliente', '99999999', '79999999', 'dup@example.com')");
    }

    // countNewThisMonth usa CURDATE() — no es testeable con SQLite in-memory
    // (misma restricción que findByResetToken / findByRememberToken con NOW())
}

<?php

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class UserRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private User $user;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->user = new User();
        $this->seedRole();
    }

    // -------------------------------------------------------------------------
    // Helpers de seed mínimos
    // -------------------------------------------------------------------------

    private function seedRole(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
    }

    private function createUser(string $email = 'test@example.com'): int
    {
        $this->user->createUser('Test User', $email, 1, 'secret123');
        return (int)$this->pdo->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // createUser
    // -------------------------------------------------------------------------

    public function test_createUser_persists_record(): void
    {
        $result = $this->user->createUser('Juan Pérez', 'juan@example.com', 1, 'password');

        $this->assertIsInt($result);

        $row = $this->user->find(1);
        $this->assertIsArray($row);
        $this->assertSame('Juan Pérez', $row['nombres']);
        $this->assertSame('juan@example.com', $row['email']);
    }

    public function test_createUser_hashes_password(): void
    {
        $this->user->createUser('Test', 'a@b.com', 1, 'secret123');

        $row = $this->user->find(1);
        $this->assertNotEquals('secret123', $row['password_user']);
        $this->assertTrue(password_verify('secret123', $row['password_user']));
    }

    // -------------------------------------------------------------------------
    // updateUser
    // -------------------------------------------------------------------------

    public function test_updateUser_without_password_does_not_rehash(): void
    {
        $this->createUser();
        $originalHash = $this->user->find(1)['password_user'];

        $this->user->updateUser(1, 'Nuevo Nombre', 'nuevo@example.com', 1, null);

        $updated = $this->user->find(1);
        $this->assertSame('Nuevo Nombre', $updated['nombres']);
        $this->assertSame($originalHash, $updated['password_user']);
    }

    public function test_updateUser_with_password_rehashes(): void
    {
        $this->createUser();

        $this->user->updateUser(1, 'Test User', 'test@example.com', 1, 'newpassword');

        $updated = $this->user->find(1);
        $this->assertTrue(password_verify('newpassword', $updated['password_user']));
    }

    // -------------------------------------------------------------------------
    // Reset token
    // -------------------------------------------------------------------------

    public function test_clearResetToken_nullifies_token(): void
    {
        $id = $this->createUser();
        $this->pdo->exec(
            "UPDATE tb_usuarios SET reset_token = 'sometoken', reset_token_expiracion = '2999-01-01 00:00:00'
             WHERE id_usuario = $id"
        );

        $result = $this->user->clearResetToken($id);

        $this->assertTrue($result);
        $row = $this->user->find($id);
        $this->assertNull($row['reset_token']);
        $this->assertNull($row['reset_token_expiracion']);
    }

    public function test_passwordMatches_accepts_correct_password_and_rejects_wrong_one(): void
    {
        $id = $this->createUser();
        $row = $this->user->find($id);

        $this->assertTrue(User::passwordMatches($row, 'secret123'));
        $this->assertFalse(User::passwordMatches($row, 'otra-cosa'));
    }

    public function test_passwordMatches_is_false_for_missing_user_even_if_password_matches_the_dummy_hash(): void
    {
        $this->assertFalse(User::passwordMatches(null, 'secret123'));
        $this->assertFalse(User::passwordMatches(null, ''));
        $this->assertFalse(User::passwordMatches(['password_user' => null], 'secret123'));
        $this->assertFalse(User::passwordMatches(['password_user' => ''], ''));
    }

    private function seedRememberSessions(int $userId, int $count = 2): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->pdo->exec(
                "INSERT INTO tb_sesiones_recordadas (id_usuario, selector, token_hash, fyh_expira, ip)
                 VALUES ($userId, 'sel-$userId-$i', 'h', '2999-01-01 00:00:00', '1.1.1.1')"
            );
        }
    }

    private function rememberSessionsOf(int $userId): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM tb_sesiones_recordadas WHERE id_usuario = $userId")->fetchColumn();
    }

    public function test_updatePassword_revokes_all_remember_sessions_of_that_user_only(): void
    {
        $id    = $this->createUser();
        $other = $this->createUser('otro@example.com');
        $this->seedRememberSessions($id);
        $this->seedRememberSessions($other, 1);

        $this->assertTrue($this->user->updatePassword($id, 'nueva-clave-1'));

        $this->assertSame(0, $this->rememberSessionsOf($id));
        $this->assertSame(1, $this->rememberSessionsOf($other));
        $this->assertTrue($this->user->verifyCurrentPassword($id, 'nueva-clave-1'));
    }

    public function test_updateUser_with_password_revokes_remember_sessions_but_without_it_keeps_them(): void
    {
        $id = $this->createUser();
        $this->seedRememberSessions($id);

        $this->user->updateUser($id, 'Nuevo Nombre', 'test@example.com', 1);
        $this->assertSame(2, $this->rememberSessionsOf($id));

        $this->user->updateUser($id, 'Nuevo Nombre', 'test@example.com', 1, 'otra-clave-123');
        $this->assertSame(0, $this->rememberSessionsOf($id));
    }

    public function test_isReferenced_false_when_no_products_or_purchases(): void
    {
        $id = $this->createUser();
        $this->assertFalse($this->user->isReferenced($id));
    }

    public function test_delete_succeeds_for_user_who_opened_the_pos_and_keeps_the_reservation(): void
    {
        $id = $this->createUser();
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES ($id)");

        $this->assertFalse($this->user->isReferenced($id));
        $this->user->delete($id);

        $this->assertNull($this->pdo->query('SELECT id_usuario FROM tb_pos_reservas')->fetchColumn());
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pos_reservas')->fetchColumn());
    }

    public function test_isReferenced_true_when_user_has_products(): void
    {
        $id = $this->createUser();
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P-001', 'Producto', 10, 5.00, 10.00, '2026-01-01', $id, 1)");

        $this->assertTrue($this->user->isReferenced($id));
    }

    public function test_isReferenced_true_when_user_has_purchases(): void
    {
        $id = $this->createUser();
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P-001', 'Producto', 10, 5.00, 10.00, '2026-01-01', $id, 1)");
        $this->pdo->exec("INSERT INTO tb_proveedores
            (nombre_proveedor, celular, empresa, direccion)
            VALUES ('Proveedor', '123', 'Empresa', 'Dir')");
        $this->pdo->exec("INSERT INTO tb_compras
            (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad)
            VALUES (1, 1, '2026-01-01', 1, 'COMP-001', $id, 5.00, 10)");

        $this->assertTrue($this->user->isReferenced($id));
    }
}

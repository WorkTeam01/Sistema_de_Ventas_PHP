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

    public function test_storeResetToken_persists_token_and_expiry(): void
    {
        $id = $this->createUser();
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $result = $this->user->storeResetToken($id, $token, $expiry);

        $this->assertTrue($result);
        $row = $this->user->find($id);
        $this->assertSame($token, $row['reset_token']);
        $this->assertSame($expiry, $row['reset_token_expiracion']);
    }

    public function test_clearResetToken_nullifies_token(): void
    {
        $id = $this->createUser();
        $this->user->storeResetToken($id, 'sometoken', date('Y-m-d H:i:s', strtotime('+1 hour')));

        $result = $this->user->clearResetToken($id);

        $this->assertTrue($result);
        $row = $this->user->find($id);
        $this->assertNull($row['reset_token']);
        $this->assertNull($row['reset_token_expiracion']);
    }

    // -------------------------------------------------------------------------
    // Remember token
    // -------------------------------------------------------------------------

    public function test_storeRememberToken_persists_hash_and_expiry(): void
    {
        $id = $this->createUser();
        $tokenHash = hash('sha256', 'plaintoken');
        $expiry = date('Y-m-d H:i:s', strtotime('+14 days'));

        $result = $this->user->storeRememberToken($id, $tokenHash, $expiry);

        $this->assertTrue($result);
        $row = $this->user->find($id);
        $this->assertSame($tokenHash, $row['remember_token']);
        $this->assertSame($expiry, $row['remember_token_expiry']);
    }

    public function test_clearRememberToken_nullifies_token(): void
    {
        $id = $this->createUser();
        $this->user->storeRememberToken($id, hash('sha256', 'x'), date('Y-m-d H:i:s', strtotime('+14 days')));

        $result = $this->user->clearRememberToken($id);

        $this->assertTrue($result);
        $row = $this->user->find($id);
        $this->assertNull($row['remember_token']);
        $this->assertNull($row['remember_token_expiry']);
    }

    // -------------------------------------------------------------------------
    // Rate limiting — isLocked, recordFailedLogin, clearLoginAttempts
    // -------------------------------------------------------------------------

    public function test_isLocked_returns_false_when_bloqueado_hasta_is_null(): void
    {
        $id = $this->createUser();
        $row = $this->user->find($id);
        $this->assertFalse($this->user->isLocked($row));
    }

    public function test_isLocked_returns_false_with_past_timestamp(): void
    {
        $id = $this->createUser();
        $past = date('Y-m-d H:i:s', time() - 1);
        $this->pdo->exec("UPDATE tb_usuarios SET login_bloqueado_hasta = '$past' WHERE id_usuario = $id");

        $row = $this->user->find($id);
        $this->assertFalse($this->user->isLocked($row));
    }

    public function test_isLocked_returns_true_with_future_timestamp(): void
    {
        $id = $this->createUser();
        $future = date('Y-m-d H:i:s', time() + 900);
        $this->pdo->exec("UPDATE tb_usuarios SET login_bloqueado_hasta = '$future' WHERE id_usuario = $id");

        $row = $this->user->find($id);
        $this->assertTrue($this->user->isLocked($row));
    }

    public function test_recordFailedLogin_does_not_lock_before_5_attempts(): void
    {
        $id = $this->createUser();

        for ($i = 0; $i < 4; $i++) {
            $this->user->recordFailedLogin($id);
        }

        $row = $this->user->find($id);
        $this->assertSame(4, (int)$row['login_intentos']);
        $this->assertNull($row['login_bloqueado_hasta']);
    }

    public function test_recordFailedLogin_locks_on_5th_attempt(): void
    {
        $id = $this->createUser();

        for ($i = 0; $i < 5; $i++) {
            $this->user->recordFailedLogin($id);
        }

        $row = $this->user->find($id);
        $this->assertTrue($this->user->isLocked($row));
    }

    public function test_clearLoginAttempts_resets_counter_and_timestamp(): void
    {
        $id = $this->createUser();
        for ($i = 0; $i < 5; $i++) {
            $this->user->recordFailedLogin($id);
        }

        $this->user->clearLoginAttempts($id);

        $row = $this->user->find($id);
        $this->assertSame(0, (int)$row['login_intentos']);
        $this->assertNull($row['login_bloqueado_hasta']);
    }

    // -------------------------------------------------------------------------
    // isReferenced — User::isReferenced verifica tb_almacen y tb_compras
    // -------------------------------------------------------------------------

    public function test_isReferenced_false_when_no_products_or_purchases(): void
    {
        $id = $this->createUser();
        $this->assertFalse($this->user->isReferenced($id));
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

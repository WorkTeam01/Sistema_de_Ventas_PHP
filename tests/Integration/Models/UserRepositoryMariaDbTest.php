<?php

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Cubre métodos de User que comparan contra NOW() (función específica de MySQL,
 * no soportada por SQLite in-memory). Ver UserRepositoryTest para el resto (SQLite).
 */
final class UserRepositoryMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private User $user;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->user = new User();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
    }

    private function createUser(string $email = 'test@example.com'): int
    {
        $this->user->createUser('Test User', $email, 1, 'secret123');
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Las fechas límite se calculan con NOW()/INTERVAL del propio MariaDB
     * (no con date()/strtotime() de PHP), porque el reloj/timezone de PHP
     * y el del servidor MariaDB pueden no coincidir en este entorno.
     */
    private function setResetTokenExpiry(int $id, string $token, string $sqlInterval): void
    {
        $this->pdo->exec("UPDATE tb_usuarios
            SET reset_token = '$token', reset_token_expiracion = $sqlInterval
            WHERE id_usuario = $id");
    }

    private function setRememberTokenExpiry(int $id, string $tokenHash, string $sqlInterval): void
    {
        $this->pdo->exec("UPDATE tb_usuarios
            SET remember_token = '$tokenHash', remember_token_expiry = $sqlInterval
            WHERE id_usuario = $id");
    }

    public function test_findByResetToken_returns_user_when_not_expired(): void
    {
        $id = $this->createUser();
        $this->setResetTokenExpiry($id, 'valid-token', 'NOW() + INTERVAL 1 HOUR');

        $row = $this->user->findByResetToken('valid-token');

        $this->assertIsArray($row);
        $this->assertSame($id, (int)$row['id_usuario']);
    }

    public function test_findByResetToken_returns_false_when_expired(): void
    {
        $id = $this->createUser();
        $this->setResetTokenExpiry($id, 'expired-token', 'NOW() - INTERVAL 1 HOUR');

        $this->assertFalse($this->user->findByResetToken('expired-token'));
    }

    public function test_findByRememberToken_returns_user_when_not_expired(): void
    {
        $id = $this->createUser();
        $this->setRememberTokenExpiry($id, 'token-hash', 'NOW() + INTERVAL 1 DAY');

        $row = $this->user->findByRememberToken($id, 'token-hash');

        $this->assertIsArray($row);
        $this->assertSame($id, (int)$row['id_usuario']);
    }

    public function test_findByRememberToken_returns_null_when_expired(): void
    {
        $id = $this->createUser();
        $this->setRememberTokenExpiry($id, 'token-hash', 'NOW() - INTERVAL 1 DAY');

        $this->assertNull($this->user->findByRememberToken($id, 'token-hash'));
    }
}

<?php

namespace Tests\Integration\Models;

use App\Core\Config;
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

    public function test_storeResetToken_saves_hash_not_plain_token_and_finds_by_plain(): void
    {
        $id = $this->createUser();
        $plain = bin2hex(random_bytes(32));

        $this->assertTrue($this->user->storeResetToken($id, hash('sha256', $plain)));

        $stored = $this->user->find($id)['reset_token'];
        $this->assertNotSame($plain, $stored);
        $this->assertSame(hash('sha256', $plain), $stored);

        $row = $this->user->findByResetToken($plain);
        $this->assertIsArray($row);
        $this->assertSame($id, (int)$row['id_usuario']);
    }

    public function test_storeResetToken_expiry_is_about_one_hour_by_db_clock(): void
    {
        $id = $this->createUser();
        Config::load();
        $saved = $_ENV['RESET_TOKEN_MINUTES'] ?? null;
        unset($_ENV['RESET_TOKEN_MINUTES']);
        try {
            $this->user->storeResetToken($id, hash('sha256', 'abc'));
        } finally {
            if ($saved !== null) {
                $_ENV['RESET_TOKEN_MINUTES'] = $saved;
            }
        }

        $minutes = (int)$this->pdo->query(
            "SELECT TIMESTAMPDIFF(MINUTE, NOW(), reset_token_expiracion)
             FROM tb_usuarios WHERE id_usuario = $id"
        )->fetchColumn();

        $this->assertGreaterThanOrEqual(59, $minutes);
        $this->assertLessThanOrEqual(60, $minutes);
    }

    public function test_findByResetToken_does_not_match_the_stored_hash_used_as_token(): void
    {
        $id = $this->createUser();
        $hash = hash('sha256', 'plain-token');
        $this->user->storeResetToken($id, $hash);

        $this->assertFalse($this->user->findByResetToken($hash));
    }

    public function test_findByResetToken_returns_false_when_expired(): void
    {
        $id = $this->createUser();
        $this->setResetTokenExpiry($id, hash('sha256', 'expired-token'), 'NOW() - INTERVAL 1 HOUR');

        $this->assertFalse($this->user->findByResetToken('expired-token'));
    }

    public function test_findByEmail_finds_legacy_mixed_case_row(): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, id_rol, password_user)
             VALUES ('Legacy', 'Legacy@Sistema.com', 1, 'x')"
        );

        $this->assertIsArray($this->user->findByEmail('legacy@sistema.com'));
    }

    public function test_storeResetToken_expiry_is_read_from_env(): void
    {
        $id = $this->createUser();
        Config::load();
        $saved = $_ENV['RESET_TOKEN_MINUTES'] ?? null;
        $_ENV['RESET_TOKEN_MINUTES'] = '10';
        try {
            $this->user->storeResetToken($id, hash('sha256', 'abc'));
        } finally {
            if ($saved === null) {
                unset($_ENV['RESET_TOKEN_MINUTES']);
            } else {
                $_ENV['RESET_TOKEN_MINUTES'] = $saved;
            }
        }

        $minutes = (int)$this->pdo->query(
            "SELECT TIMESTAMPDIFF(MINUTE, NOW(), reset_token_expiracion) FROM tb_usuarios WHERE id_usuario = $id"
        )->fetchColumn();

        $this->assertGreaterThanOrEqual(9, $minutes);
        $this->assertLessThanOrEqual(10, $minutes);
    }
}

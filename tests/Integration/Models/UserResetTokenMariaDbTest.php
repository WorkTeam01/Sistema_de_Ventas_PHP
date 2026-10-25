<?php

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * MariaDB evalúa las asignaciones de un UPDATE de izquierda a derecha; SQLite usa siempre el
 * valor previo. Este test fija que el enlace de restablecimiento se anula al cambiar el correo
 * con el motor de producción.
 */
final class UserResetTokenMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private User $user;
    private int $userId;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->user = new User();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('RolTest')");
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol, reset_token, reset_token_expiracion)
             VALUES ('Ana', 'ana@test.com', 'x', 1, 'pendiente', NOW() + INTERVAL 1 HOUR)"
        );
        $this->userId = (int) $this->pdo->lastInsertId();
    }

    private function resetToken(): ?string
    {
        $value = $this->pdo->query("SELECT reset_token FROM tb_usuarios WHERE id_usuario = {$this->userId}")->fetchColumn();

        return $value === false ? null : $value;
    }

    public function test_updateProfileInfo_keeps_token_when_email_is_the_same(): void
    {
        $this->user->updateProfileInfo($this->userId, 'Ana Nueva', 'ana@test.com');

        $this->assertSame('pendiente', $this->resetToken());
    }

    public function test_updateProfileInfo_clears_token_when_email_changes(): void
    {
        $this->user->updateProfileInfo($this->userId, 'Ana', 'otra@test.com');

        $this->assertNull($this->resetToken());
    }

    public function test_updateUser_clears_token_when_email_changes(): void
    {
        $this->user->updateUser($this->userId, 'Ana', 'otra@test.com', 1);

        $this->assertNull($this->resetToken());
    }

    public function test_updateUser_keeps_token_when_email_is_the_same(): void
    {
        $this->user->updateUser($this->userId, 'Ana Nueva', 'ana@test.com', 1);

        $this->assertSame('pendiente', $this->resetToken());
    }
}

<?php

namespace Tests\Integration\Models;

use App\Models\User;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class UserAccountSafetyTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private User $user;
    private int $superRole;
    private int $plainRole;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->user = new User();

        $this->pdo->exec("INSERT INTO tb_permisos (clave, descripcion, modulo) VALUES ('is_superadmin', 'Superusuario', 'sistema')");
        $permissionId = (int) $this->pdo->lastInsertId();

        $this->superRole = $this->insertRole('SuperTest');
        $this->plainRole = $this->insertRole('PlanoTest');
        $this->pdo->prepare('INSERT INTO tb_rol_permiso (id_rol, id_permiso) VALUES (?, ?)')
            ->execute([$this->superRole, $permissionId]);
    }

    private function insertRole(string $name): int
    {
        $this->pdo->prepare('INSERT INTO tb_roles (rol) VALUES (?)')->execute([$name]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertUser(string $email, int $roleId, ?string $resetToken = null): int
    {
        $this->pdo->prepare(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol, reset_token, reset_token_expiracion)
             VALUES ('Usuario Test', ?, 'x', ?, ?, ?)"
        )->execute([$email, $roleId, $resetToken, $resetToken === null ? null : '2999-01-01 00:00:00']);

        return (int) $this->pdo->lastInsertId();
    }

    private function resetToken(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT reset_token FROM tb_usuarios WHERE id_usuario = ?');
        $stmt->execute([$id]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : $value;
    }

    public function test_isLastSuperadminUser_true_when_only_one_user_holds_superadmin(): void
    {
        $admin = $this->insertUser('admin@test.local', $this->superRole);
        $this->insertUser('otro@test.local', $this->plainRole);

        $this->assertTrue($this->user->isLastSuperadminUser($admin));
    }

    public function test_isLastSuperadminUser_false_when_another_user_holds_superadmin(): void
    {
        $admin = $this->insertUser('admin@test.local', $this->superRole);
        $this->insertUser('admin2@test.local', $this->superRole);

        $this->assertFalse($this->user->isLastSuperadminUser($admin));
    }

    public function test_isLastSuperadminUser_false_for_user_without_superadmin(): void
    {
        $this->insertUser('admin@test.local', $this->superRole);
        $plain = $this->insertUser('otro@test.local', $this->plainRole);

        $this->assertFalse($this->user->isLastSuperadminUser($plain));
    }

    public function test_updateProfileInfo_clears_reset_token_only_when_email_changes(): void
    {
        $id = $this->insertUser('viejo@test.local', $this->plainRole, 'hash-pendiente');

        $this->user->updateProfileInfo($id, 'Nuevo Nombre', 'viejo@test.local');
        $this->assertSame('hash-pendiente', $this->resetToken($id));

        $this->user->updateProfileInfo($id, 'Nuevo Nombre', 'nuevo@test.local');
        $this->assertNull($this->resetToken($id));
    }

    public function test_updateUser_clears_reset_token_when_email_changes(): void
    {
        $id = $this->insertUser('viejo@test.local', $this->plainRole, 'hash-pendiente');

        $this->user->updateUser($id, 'Usuario Test', 'viejo@test.local', $this->plainRole);
        $this->assertSame('hash-pendiente', $this->resetToken($id));

        $this->user->updateUser($id, 'Usuario Test', 'nuevo@test.local', $this->plainRole);
        $this->assertNull($this->resetToken($id));
    }

    public function test_updatePassword_clears_reset_token(): void
    {
        $id = $this->insertUser('a@test.local', $this->plainRole, 'hash-pendiente');

        $this->user->updatePassword($id, 'nueva-clave-segura');

        $this->assertNull($this->resetToken($id));
    }
}

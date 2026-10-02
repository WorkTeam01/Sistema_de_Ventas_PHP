<?php

namespace Tests\Integration\Core;

use App\Core\Auth;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

/**
 * check() revalida la sesión contra la BD: una cuenta eliminada o degradada
 * pierde sus permisos en la siguiente petición en vez de conservarlos hasta el
 * timeout de inactividad.
 */
final class AuthCheckTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    protected function setUp(): void
    {
        $this->setUpDatabase();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $this->resetAuthCache();
        $this->seedRolesAndUsers();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $this->resetAuthCache();
        parent::tearDown();
    }

    private function resetAuthCache(): void
    {
        $prop = new \ReflectionProperty(Auth::class, 'cachedUser');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    private function seedRolesAndUsers(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador'), ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_permisos (clave, descripcion, modulo) VALUES
                          ('is_superadmin', 'Superusuario', 'sistema'),
                          ('view_dashboard', 'Ver dashboard', 'dashboard')");
        $this->pdo->exec("INSERT INTO tb_rol_permiso (id_rol, id_permiso) VALUES (1,1),(1,2),(2,2)");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('Admin', 'admin@test.com', 'hash', 1)");
    }

    private function startSessionAs(string $email): void
    {
        $_SESSION['sesion_email'] = $email;
        Auth::refreshPermissions();
    }

    public function test_check_returns_false_without_session_email(): void
    {
        $this->assertFalse(Auth::check());
    }

    public function test_check_returns_true_for_still_valid_account(): void
    {
        $this->startSessionAs('admin@test.com');
        $version = (int)$_SESSION['permisos_version'];

        $this->assertTrue(Auth::check());
        $this->assertTrue(Auth::can('is_superadmin'));
        $this->assertSame($version, (int)$_SESSION['permisos_version']);
    }

    public function test_check_logs_out_when_account_was_deleted(): void
    {
        $this->startSessionAs('admin@test.com');

        $this->pdo->exec("DELETE FROM tb_usuarios WHERE email = 'admin@test.com'");

        $this->assertFalse(Auth::check());
        $this->assertEmpty($_SESSION['sesion_email'] ?? null);
    }

    public function test_check_reloads_permissions_when_role_changed_in_db(): void
    {
        $this->startSessionAs('admin@test.com');
        $this->assertTrue(Auth::can('is_superadmin'));

        $this->pdo->exec("UPDATE tb_usuarios SET id_rol = 2 WHERE email = 'admin@test.com'");

        $this->assertTrue(Auth::check());
        $this->assertSame(2, (int)$_SESSION['id_rol']);
        $this->assertFalse(Auth::can('is_superadmin'));
        $this->assertTrue(Auth::can('view_dashboard'));
    }

    public function test_check_reloads_permissions_when_version_changed_in_db(): void
    {
        $this->startSessionAs('admin@test.com');
        $this->assertTrue(Auth::can('is_superadmin'));

        // Otro admin revoca un permiso del rol desde otra sesión.
        $this->pdo->exec("DELETE FROM tb_rol_permiso WHERE id_rol = 1 AND id_permiso = 1");
        $this->pdo->exec("UPDATE tb_roles SET permisos_version = permisos_version + 1 WHERE id_rol = 1");

        $this->assertTrue(Auth::check());
        $this->assertFalse(Auth::can('is_superadmin'));
        $this->assertSame(
            (int)$this->pdo->query("SELECT permisos_version FROM tb_roles WHERE id_rol = 1")->fetchColumn(),
            (int)$_SESSION['permisos_version']
        );
    }
}

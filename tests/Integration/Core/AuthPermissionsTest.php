<?php

namespace Tests\Integration\Core;

use App\Core\Auth;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class AuthPermissionsTest extends TestCase
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

        $this->seedRolesAndPermisos();
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

    private function seedRolesAndPermisos(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");

        $this->pdo->exec("INSERT INTO tb_permisos (clave, descripcion, modulo) VALUES ('is_superadmin', 'Superusuario', 'sistema')");
        $this->pdo->exec("INSERT INTO tb_permisos (clave, descripcion, modulo) VALUES ('view_dashboard', 'Ver dashboard', 'dashboard')");
        $this->pdo->exec("INSERT INTO tb_permisos (clave, descripcion, modulo) VALUES ('manage_users', 'Gestionar usuarios', 'usuarios')");

        // Administrador: todos los permisos
        $this->pdo->exec("INSERT INTO tb_rol_permiso (id_rol, id_permiso) VALUES (1,1),(1,2),(1,3)");
        // Vendedor: solo view_dashboard
        $this->pdo->exec("INSERT INTO tb_rol_permiso (id_rol, id_permiso) VALUES (2,2)");
    }

    public function test_admin_has_all_seeded_permissions_after_login(): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Admin', 'admin@test.com', 'hash', 1)"
        );

        $_SESSION['sesion_email'] = 'admin@test.com';
        Auth::refreshPermissions();

        $this->assertTrue(Auth::can('is_superadmin'));
        $this->assertTrue(Auth::can('view_dashboard'));
        $this->assertTrue(Auth::can('manage_users'));
        $this->assertTrue(Auth::isAdmin());
    }

    public function test_vendedor_has_only_assigned_permissions(): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Vend', 'vend@test.com', 'hash', 2)"
        );

        $_SESSION['sesion_email'] = 'vend@test.com';
        Auth::refreshPermissions();

        $this->assertTrue(Auth::can('view_dashboard'));
        $this->assertFalse(Auth::can('is_superadmin'));
        $this->assertFalse(Auth::can('manage_users'));
        $this->assertFalse(Auth::isAdmin());
    }

    public function test_permissions_loaded_contain_correct_keys(): void
    {
        $this->pdo->exec(
            "INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Admin2', 'admin2@test.com', 'hash', 1)"
        );

        $_SESSION['sesion_email'] = 'admin2@test.com';
        Auth::refreshPermissions();

        $permisos = $_SESSION['permisos'];
        $this->assertContains('is_superadmin', $permisos);
        $this->assertContains('view_dashboard', $permisos);
        $this->assertContains('manage_users', $permisos);
        $this->assertCount(3, $permisos);
    }
}

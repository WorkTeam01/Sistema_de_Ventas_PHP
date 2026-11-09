<?php

namespace Tests\Integration\Models;

use App\Models\Permission;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class PermissionRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Permission $permission;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->permission = new Permission();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createPermission(
        string $clave = 'manage_roles',
        string $descripcion = 'Gestionar roles',
        string $modulo = 'roles'
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tb_permisos (clave, descripcion, modulo) VALUES (?, ?, ?)"
        );
        $stmt->execute([$clave, $descripcion, $modulo]);
        return (int) $this->pdo->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // claveExists
    // -------------------------------------------------------------------------

    public function test_claveExists_returns_false_when_clave_not_in_table(): void
    {
        $this->assertFalse($this->permission->claveExists('manage_roles'));
    }

    public function test_claveExists_returns_true_when_clave_exists(): void
    {
        $this->createPermission('manage_roles');

        $this->assertTrue($this->permission->claveExists('manage_roles'));
    }

    public function test_claveExists_excludes_own_id_when_editing(): void
    {
        $id = $this->createPermission('manage_roles');

        $this->assertFalse($this->permission->claveExists('manage_roles', $id));
    }

    public function test_claveExists_detects_duplicate_for_different_id(): void
    {
        $this->createPermission('manage_roles');
        $otherId = $this->createPermission('view_roles', 'Ver roles', 'roles');

        $this->assertTrue($this->permission->claveExists('manage_roles', $otherId));
    }

    // -------------------------------------------------------------------------
    // allGroupedByModulo
    // -------------------------------------------------------------------------

    public function test_allGroupedByModulo_returns_empty_array_on_empty_table(): void
    {
        $this->assertSame([], $this->permission->allGroupedByModulo());
    }

    public function test_allGroupedByModulo_groups_by_modulo(): void
    {
        $this->createPermission('manage_roles', 'Gestionar roles', 'roles');
        $this->createPermission('view_roles', 'Ver roles', 'roles');
        $this->createPermission('manage_users', 'Gestionar usuarios', 'usuarios');

        $grouped = $this->permission->allGroupedByModulo();

        $this->assertSame(['roles', 'usuarios'], array_keys($grouped));
        $this->assertCount(2, $grouped['roles']);
        $this->assertCount(1, $grouped['usuarios']);
    }

    public function test_allGroupedByModulo_orders_by_modulo_then_clave(): void
    {
        $this->createPermission('view_roles', 'Ver roles', 'roles');
        $this->createPermission('manage_roles', 'Gestionar roles', 'roles');

        $grouped = $this->permission->allGroupedByModulo();

        $this->assertSame('manage_roles', $grouped['roles'][0]['clave']);
        $this->assertSame('view_roles', $grouped['roles'][1]['clave']);
    }

    // -------------------------------------------------------------------------
    // create / find (heredados de Model)
    // -------------------------------------------------------------------------

    public function test_create_persists_record(): void
    {
        $id = $this->permission->create([
            'clave' => 'manage_roles',
            'descripcion' => 'Gestionar roles',
            'modulo' => 'roles',
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $row = $this->permission->find($id);
        $this->assertIsArray($row);
        $this->assertSame('manage_roles', $row['clave']);
    }

    public function test_updateDetails_changes_descripcion_and_modulo_but_never_clave(): void
    {
        $id = $this->permission->create([
            'clave' => 'view_dashboard',
            'descripcion' => 'Ver dashboard',
            'modulo' => 'dashboard',
        ]);

        $this->assertTrue($this->permission->updateDetails($id, 'Nueva descripción', 'general'));

        $row = $this->permission->find($id);
        $this->assertSame('view_dashboard', $row['clave']);
        $this->assertSame('Nueva descripción', $row['descripcion']);
        $this->assertSame('general', $row['modulo']);
    }

    public function test_find_returns_false_for_nonexistent_id(): void
    {
        $this->assertFalse($this->permission->find(999));
    }

    public function test_update_details_changes_description_and_module_but_update_rejects_the_key(): void
    {
        $id = $this->createPermission('manage_roles', 'Gestionar roles', 'roles');

        $this->assertTrue($this->permission->updateDetails($id, 'Nueva descripción', 'seguridad'));

        try {
            $this->permission->update($id, ['clave' => 'is_superadmin']);
            $this->fail('update aceptó clave');
        } catch (\InvalidArgumentException) {
            $row = $this->pdo->query("SELECT clave, descripcion, modulo FROM tb_permisos WHERE id_permiso = $id")->fetch();
            $this->assertSame(['clave' => 'manage_roles', 'descripcion' => 'Nueva descripción', 'modulo' => 'seguridad'], $row);
        }
    }

    public function test_create_accepts_the_key_and_rejects_other_columns(): void
    {
        $this->assertGreaterThan(0, $this->permission->create(['clave' => 'view_x', 'descripcion' => 'Ver x', 'modulo' => 'x']));

        $this->expectException(\InvalidArgumentException::class);
        $this->permission->create(['clave' => 'view_y', 'descripcion' => 'Ver y', 'modulo' => 'y', 'id_permiso' => 50]);
    }
}

<?php

namespace Tests\Integration\Models;

use App\Models\Role;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class RoleRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Role $role;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->role = new Role();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createRole(string $name = 'Vendedor'): int
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('$name')");
        return (int)$this->pdo->lastInsertId();
    }

    private function createPermission(string $clave, string $modulo = 'general'): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO tb_permisos (clave, descripcion, modulo) VALUES (?, ?, ?)');
        $stmt->execute([$clave, $clave, $modulo]);
        return (int) $this->pdo->lastInsertId();
    }

    private function getPermisosVersion(int $roleId): int
    {
        $stmt = $this->pdo->prepare('SELECT permisos_version FROM tb_roles WHERE id_rol = ?');
        $stmt->execute([$roleId]);
        return (int) $stmt->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // nameExists
    // -------------------------------------------------------------------------

    public function test_nameExists_returns_false_when_name_not_in_table(): void
    {
        $this->assertFalse($this->role->nameExists('Administrador'));
    }

    public function test_nameExists_returns_true_when_name_exists(): void
    {
        $this->createRole('Administrador');

        $this->assertTrue($this->role->nameExists('Administrador'));
    }

    public function test_nameExists_is_case_sensitive(): void
    {
        $this->createRole('Administrador');

        $this->assertFalse($this->role->nameExists('administrador'));
    }

    public function test_nameExists_excludes_own_id_when_editing(): void
    {
        $id = $this->createRole('Vendedor');

        $this->assertFalse($this->role->nameExists('Vendedor', $id));
    }

    public function test_nameExists_detects_duplicate_for_different_id(): void
    {
        $this->createRole('Vendedor');
        $otherId = $this->createRole('Comprador');

        $this->assertTrue($this->role->nameExists('Vendedor', $otherId));
    }

    // -------------------------------------------------------------------------
    // all / find / create / delete (heredados de Model)
    // -------------------------------------------------------------------------

    public function test_all_returns_empty_array_on_empty_table(): void
    {
        $this->assertSame([], $this->role->all());
    }

    public function test_create_persists_record(): void
    {
        $id = $this->role->create(['rol' => 'Auditor']);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $row = $this->role->find($id);
        $this->assertIsArray($row);
        $this->assertSame('Auditor', $row['rol']);
    }

    public function test_all_returns_all_records(): void
    {
        $this->createRole('A');
        $this->createRole('B');

        $this->assertCount(2, $this->role->all());
    }

    public function test_find_returns_false_for_nonexistent_id(): void
    {
        $this->assertFalse($this->role->find(999));
    }

    public function test_delete_removes_record(): void
    {
        $id = $this->createRole('Temporal');

        $this->role->delete($id);

        $this->assertFalse($this->role->find($id));
    }

    // -------------------------------------------------------------------------
    // getAssignedPermissionIds / syncPermissions
    // -------------------------------------------------------------------------

    public function test_getAssignedPermissionIds_returns_empty_array_when_no_permissions_assigned(): void
    {
        $roleId = $this->createRole();

        $this->assertSame([], $this->role->getAssignedPermissionIds($roleId));
    }

    public function test_getAssignedPermissionIds_returns_assigned_ids(): void
    {
        $roleId = $this->createRole();
        $p1 = $this->createPermission('view_products');
        $p2 = $this->createPermission('manage_products');
        $this->pdo->exec("INSERT INTO tb_rol_permiso (id_rol, id_permiso) VALUES ({$roleId}, {$p1}), ({$roleId}, {$p2})");

        $ids = $this->role->getAssignedPermissionIds($roleId);

        $this->assertCount(2, $ids);
        $this->assertContains($p1, $ids);
        $this->assertContains($p2, $ids);
    }

    public function test_syncPermissions_assigns_new_permissions(): void
    {
        $roleId = $this->createRole();
        $p1 = $this->createPermission('view_products');
        $p2 = $this->createPermission('manage_products');

        $result = $this->role->syncPermissions($roleId, [$p1, $p2]);

        $this->assertTrue($result);
        $this->assertEqualsCanonicalizing([$p1, $p2], $this->role->getAssignedPermissionIds($roleId));
    }

    public function test_syncPermissions_replaces_previous_set(): void
    {
        $roleId = $this->createRole();
        $p1 = $this->createPermission('view_products');
        $p2 = $this->createPermission('manage_products');
        $this->role->syncPermissions($roleId, [$p1]);

        $result = $this->role->syncPermissions($roleId, [$p2]);

        $this->assertTrue($result);
        $this->assertSame([$p2], $this->role->getAssignedPermissionIds($roleId));
    }

    public function test_syncPermissions_with_empty_array_removes_all_permissions(): void
    {
        $roleId = $this->createRole();
        $p1 = $this->createPermission('view_products');
        $this->role->syncPermissions($roleId, [$p1]);

        $result = $this->role->syncPermissions($roleId, []);

        $this->assertTrue($result);
        $this->assertSame([], $this->role->getAssignedPermissionIds($roleId));
    }

    public function test_syncPermissions_increments_permisos_version_by_one(): void
    {
        $roleId = $this->createRole();
        $p1 = $this->createPermission('view_products');
        $before = $this->getPermisosVersion($roleId);

        $this->role->syncPermissions($roleId, [$p1]);

        $this->assertSame($before + 1, $this->getPermisosVersion($roleId));
    }

    public function test_syncPermissions_increments_permisos_version_on_each_call(): void
    {
        $roleId = $this->createRole();
        $p1 = $this->createPermission('view_products');

        $this->role->syncPermissions($roleId, [$p1]);
        $this->role->syncPermissions($roleId, []);

        $this->assertSame(2, $this->getPermisosVersion($roleId));
    }

    // -------------------------------------------------------------------------
    // isLastSuperadminRole
    // -------------------------------------------------------------------------

    public function test_isLastSuperadminRole_is_true_only_while_no_other_role_grants_it(): void
    {
        $superadmin = $this->createPermission('is_superadmin');
        $first  = $this->createRole('Dueño');
        $second = $this->createRole('Socio');
        $this->role->syncPermissions($first, [$superadmin]);

        $this->assertTrue($this->role->isLastSuperadminRole($first));

        $this->role->syncPermissions($second, [$superadmin]);

        $this->assertFalse($this->role->isLastSuperadminRole($first));
        $this->assertFalse($this->role->isLastSuperadminRole($second));
    }

    public function test_isLastSuperadminRole_is_false_for_a_role_without_is_superadmin(): void
    {
        $superadmin = $this->createPermission('is_superadmin');
        $owner    = $this->createRole('Dueño');
        $operator = $this->createRole('Operador');
        $this->role->syncPermissions($owner, [$superadmin]);

        $this->assertFalse($this->role->isLastSuperadminRole($operator));
        $this->assertFalse($this->role->isLastSuperadminRole(9999));
    }

    // -------------------------------------------------------------------------
    // grantsSuperadmin
    // -------------------------------------------------------------------------

    public function test_grantsSuperadmin_returns_true_when_role_has_is_superadmin(): void
    {
        $roleId = $this->createRole('Dueño');
        $this->role->syncPermissions($roleId, [$this->createPermission('is_superadmin')]);

        $this->assertTrue($this->role->grantsSuperadmin($roleId));
    }

    public function test_grantsSuperadmin_returns_false_when_role_has_only_other_permissions(): void
    {
        $roleId = $this->createRole('Vendedor');
        $this->role->syncPermissions($roleId, [
            $this->createPermission('manage_users'),
            $this->createPermission('manage_roles'),
        ]);

        $this->assertFalse($this->role->grantsSuperadmin($roleId));
    }

    public function test_grantsSuperadmin_returns_false_for_role_without_permissions_or_nonexistent(): void
    {
        $this->createPermission('is_superadmin');
        $emptyRoleId = $this->createRole('Vacío');

        $this->assertFalse($this->role->grantsSuperadmin($emptyRoleId));
        $this->assertFalse($this->role->grantsSuperadmin(9999));
    }

    public function test_grantsSuperadmin_depends_on_the_permission_not_on_the_role_name(): void
    {
        $superadminPermission = $this->createPermission('is_superadmin');
        $namedAdmin = $this->createRole('Administrador');
        $renamed    = $this->createRole('Operador');
        $this->role->syncPermissions($renamed, [$superadminPermission]);

        $this->assertFalse($this->role->grantsSuperadmin($namedAdmin));
        $this->assertTrue($this->role->grantsSuperadmin($renamed));
    }

    public function test_grantsSuperadmin_turns_false_after_the_permission_is_revoked(): void
    {
        $roleId = $this->createRole('Temporal');
        $this->role->syncPermissions($roleId, [$this->createPermission('is_superadmin')]);
        $this->assertTrue($this->role->grantsSuperadmin($roleId));

        $this->role->syncPermissions($roleId, []);

        $this->assertFalse($this->role->grantsSuperadmin($roleId));
    }
}

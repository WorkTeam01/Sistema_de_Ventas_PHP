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
}

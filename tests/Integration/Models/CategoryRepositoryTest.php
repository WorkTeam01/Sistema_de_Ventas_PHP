<?php

namespace Tests\Integration\Models;

use App\Models\Category;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class CategoryRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Category $category;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->category = new Category();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createCategory(string $name = 'Electrónica'): int
    {
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('$name')");
        return (int)$this->pdo->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // nameExists — nombre nuevo
    // -------------------------------------------------------------------------

    public function test_nameExists_returns_false_when_name_not_in_table(): void
    {
        $this->assertFalse($this->category->nameExists('Ropa'));
    }

    public function test_nameExists_returns_true_when_name_exists(): void
    {
        $this->createCategory('Ropa');

        $this->assertTrue($this->category->nameExists('Ropa'));
    }

    public function test_nameExists_is_case_sensitive(): void
    {
        $this->createCategory('Ropa');

        $this->assertFalse($this->category->nameExists('ropa'));
    }

    // -------------------------------------------------------------------------
    // nameExists — excluir ID al editar
    // -------------------------------------------------------------------------

    public function test_nameExists_excludes_own_id_when_editing(): void
    {
        $id = $this->createCategory('Electrónica');

        $this->assertFalse($this->category->nameExists('Electrónica', $id));
    }

    public function test_nameExists_detects_duplicate_for_different_id(): void
    {
        $this->createCategory('Electrónica');
        $otherId = $this->createCategory('Ropa');

        $this->assertTrue($this->category->nameExists('Electrónica', $otherId));
    }

    // -------------------------------------------------------------------------
    // all / find / create / delete (heredados de Model)
    // -------------------------------------------------------------------------

    public function test_all_returns_empty_array_on_empty_table(): void
    {
        $this->assertSame([], $this->category->all());
    }

    public function test_create_persists_record(): void
    {
        $id = $this->category->create(['nombre_categoria' => 'Herramientas']);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $row = $this->category->find($id);
        $this->assertIsArray($row);
        $this->assertSame('Herramientas', $row['nombre_categoria']);
    }

    public function test_all_returns_all_records(): void
    {
        $this->createCategory('A');
        $this->createCategory('B');

        $this->assertCount(2, $this->category->all());
    }

    public function test_find_returns_false_for_nonexistent_id(): void
    {
        $this->assertFalse($this->category->find(999));
    }

    public function test_delete_removes_record(): void
    {
        $id = $this->createCategory('Temporal');

        $this->category->delete($id);

        $this->assertFalse($this->category->find($id));
    }
}

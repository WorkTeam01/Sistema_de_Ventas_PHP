<?php

namespace Tests\Unit\Core;

use App\Core\Database;
use App\Core\Model;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Model::create()/update() rechazan nombres de columna con forma de SQL y, cuando el modelo declara una
 * lista, cualquier columna que no esté en ella: la excepción salta antes de preparar la sentencia.
 */
final class ModelWhitelistTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec('CREATE TABLE tb_demo (id INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT, secreto TEXT)');
        $this->pdo->exec("INSERT INTO tb_demo (nombre, secreto) VALUES ('a', 's')");
        Database::set($this->pdo);
    }

    protected function tearDown(): void
    {
        Database::set(new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]));
        parent::tearDown();
    }

    /** @param list<string> $create @param list<string> $update */
    private function model(array $create, array $update): Model
    {
        return new class ($create, $update) extends Model {
            protected string $table = 'tb_demo';

            public function __construct(array $create, array $update)
            {
                parent::__construct();
                $this->createColumns = $create;
                $this->updateColumns = $update;
            }
        };
    }

    private function rows(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM tb_demo')->fetchColumn();
    }

    public function test_an_allowed_column_is_written(): void
    {
        $model = $this->model(['nombre'], ['nombre']);

        $id = $model->create(['nombre' => 'b']);
        $this->assertSame(2, $this->rows());
        $this->assertTrue($model->update($id, ['nombre' => 'c']));
        $this->assertSame('c', $this->pdo->query("SELECT nombre FROM tb_demo WHERE id = $id")->fetchColumn());
    }

    public function test_a_column_outside_the_create_list_throws_and_inserts_nothing(): void
    {
        $model = $this->model(['nombre'], ['nombre']);

        try {
            $model->create(['nombre' => 'b', 'secreto' => 'x']);
            $this->fail('Se esperaba InvalidArgumentException');
        } catch (InvalidArgumentException) {
            $this->assertSame(1, $this->rows());
        }
    }

    public function test_a_column_outside_the_update_list_throws_and_changes_nothing(): void
    {
        $model = $this->model(['nombre'], ['nombre']);

        try {
            $model->update(1, ['nombre' => 'z', 'secreto' => 'x']);
            $this->fail('Se esperaba InvalidArgumentException');
        } catch (InvalidArgumentException) {
            $row = $this->pdo->query('SELECT nombre, secreto FROM tb_demo WHERE id = 1')->fetch();
            $this->assertSame(['nombre' => 'a', 'secreto' => 's'], $row);
        }
    }

    public function test_an_empty_list_rejects_every_column(): void
    {
        $model = $this->model([], []);

        $this->expectException(InvalidArgumentException::class);
        $model->create(['nombre' => 'b']);
    }

    public function test_a_model_without_declared_lists_writes_nothing(): void
    {
        $model = new class extends Model {
            protected string $table = 'tb_demo';
        };

        try {
            $model->create(['nombre' => 'b']);
            $this->fail('create aceptó una columna sin lista declarada');
        } catch (InvalidArgumentException) {
        }
        $this->expectException(InvalidArgumentException::class);
        $model->update(1, ['nombre' => 'b']);
    }

    /** @return array<string, array{string}> */
    public static function invalidNames(): array
    {
        return [
            'punto y coma' => ['a;b'],
            'mayúscula'    => ['A'],
            'empieza en dígito' => ['1x'],
            'espacio'      => ['a b'],
            'comilla'      => ["a'b"],
            'vacío'        => [''],
            'con guion'    => ['a-b'],
        ];
    }

    #[DataProvider('invalidNames')]
    public function test_an_invalid_column_name_throws_before_the_list_is_consulted(string $name): void
    {
        $model = $this->model(['nombre'], ['nombre']);

        try {
            $model->create([$name => 'x']);
            $this->fail('create no validó');
        } catch (InvalidArgumentException) {
        }
        try {
            $model->update(1, [$name => 'x']);
            $this->fail('update no validó');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame(1, $this->rows());
        $this->assertSame('a', $this->pdo->query('SELECT nombre FROM tb_demo WHERE id = 1')->fetchColumn());
    }

    public function test_the_deprecated_insert_is_validated_too(): void
    {
        $model = $this->model(['nombre'], ['nombre']);

        try {
            $model->insert(['secreto' => 'x']);
            $this->fail('insert no validó');
        } catch (InvalidArgumentException) {
            $this->assertSame(1, $this->rows());
        }
    }
}

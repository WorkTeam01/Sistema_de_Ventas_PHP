<?php

namespace Tests\Integration\Models;

use App\Core\Model;
use App\Models\ActivityLog;
use App\Models\AuthAttempt;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\PosReservation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RememberToken;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

/**
 * Todo modelo concreto declara sus columnas escribibles: una columna ajena se rechaza y cada columna
 * declarada existe en el esquema, de modo que una lista no puede quedar desfasada de la tabla.
 */
final class ModelWhitelistCoverageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{class-string<Model>}> */
    public static function models(): array
    {
        $classes = [
            ActivityLog::class, AuthAttempt::class, CartItem::class, Category::class, Client::class,
            PaymentMethod::class, Permission::class, PosReservation::class, Product::class, Purchase::class,
            RememberToken::class, Role::class, Sale::class, SalePayment::class, SaleReturn::class,
            StockAdjustment::class, Supplier::class, User::class,
        ];

        return array_combine(
            array_map(static fn(string $class): string => substr($class, strrpos($class, '\\') + 1), $classes),
            array_map(static fn(string $class): array => [$class], $classes)
        );
    }

    public function test_the_provider_lists_every_concrete_model(): void
    {
        $declared = array_map(
            static fn(string $file): string => 'App\\Models\\' . basename($file, '.php'),
            glob(dirname(__DIR__, 3) . '/app/Models/*.php')
        );
        $concrete = array_filter($declared, static fn(string $class): bool => is_subclass_of($class, Model::class));

        $this->assertEqualsCanonicalizing($concrete, array_column(self::models(), 0));
    }

    #[DataProvider('models')]
    public function test_a_foreign_column_is_rejected_on_create_and_update(string $class): void
    {
        $model = new $class();

        foreach (['create' => [['columna_ajena' => 1]], 'update' => [1, ['columna_ajena' => 1]]] as $method => $args) {
            try {
                $model->$method(...$args);
                $this->fail("{$class}::{$method}() aceptó una columna ajena");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    #[DataProvider('models')]
    public function test_every_declared_column_exists_in_the_schema(string $class): void
    {
        $model = new $class();
        $read  = static function (Model $m, string $name) {
            return (new \ReflectionProperty(Model::class, $name))->getValue($m);
        };

        $table   = $read($model, 'table');
        $columns = array_column($this->pdo->query("PRAGMA table_info({$table})")->fetchAll(), 'name');
        $this->assertNotEmpty($columns, "Tabla {$table} ausente del esquema de pruebas");

        foreach (['createColumns', 'updateColumns'] as $list) {
            $this->assertIsArray($read($model, $list), "{$class}::\${$list} no es un arreglo");
            $this->assertSame([], array_diff($read($model, $list), $columns), "{$class}::\${$list} declara columnas inexistentes en {$table}");
        }
    }
}

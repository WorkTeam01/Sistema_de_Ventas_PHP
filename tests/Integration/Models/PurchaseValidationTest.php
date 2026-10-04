<?php

namespace Tests\Integration\Models;

use App\Models\Purchase;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class PurchaseValidationTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private Purchase $purchase;

    private array $validData = [
        'id_producto'   => 1,
        'id_proveedor'  => 1,
        'nro_compra'    => 1,
        'fecha_compra'  => '2026-01-15',
        'comprobante'   => 'FAC-001',
        'precio_compra' => 10.00,
        'cantidad'      => 5,
    ];

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->purchase = new Purchase();
    }

    // -------------------------------------------------------------------------
    // Datos válidos
    // -------------------------------------------------------------------------

    public function test_validateData_returns_true_for_valid_data(): void
    {
        $this->assertTrue($this->purchase->validateData($this->validData));
    }

    // -------------------------------------------------------------------------
    // Campos requeridos ausentes
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('missingFieldProvider')]
    public function test_validateData_returns_error_for_missing_field(string $field): void
    {
        $data = $this->validData;
        unset($data[$field]);

        $result = $this->purchase->validateData($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey($field, $result);
    }

    public static function missingFieldProvider(): array
    {
        return [
            'id_producto'  => ['id_producto'],
            'id_proveedor' => ['id_proveedor'],
            'nro_compra'   => ['nro_compra'],
            'fecha_compra' => ['fecha_compra'],
            'comprobante'  => ['comprobante'],
            'precio_compra'=> ['precio_compra'],
            'cantidad'     => ['cantidad'],
        ];
    }

    // -------------------------------------------------------------------------
    // precio_compra
    // -------------------------------------------------------------------------

    public function test_validateData_rejects_zero_price(): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, ['precio_compra' => 0]));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('precio_compra', $result);
    }

    public function test_validateData_rejects_negative_price(): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, ['precio_compra' => -5]));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('precio_compra', $result);
    }

    public function test_validateData_rejects_non_numeric_price(): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, ['precio_compra' => 'abc']));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('precio_compra', $result);
    }

    public function test_validateData_accepts_string_numeric_price(): void
    {
        $this->assertTrue($this->purchase->validateData(array_merge($this->validData, ['precio_compra' => '10.50'])));
    }

    // -------------------------------------------------------------------------
    // cantidad
    // -------------------------------------------------------------------------

    public function test_validateData_rejects_zero_quantity(): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, ['cantidad' => 0]));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('cantidad', $result);
    }

    public function test_validateData_rejects_negative_quantity(): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, ['cantidad' => -1]));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('cantidad', $result);
    }

    public function test_validateData_rejects_non_numeric_quantity(): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, ['cantidad' => 'abc']));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('cantidad', $result);
    }

    // -------------------------------------------------------------------------
    // comprobante
    // -------------------------------------------------------------------------

    public function test_validateData_rejects_comprobante_shorter_than_3_chars(): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, ['comprobante' => 'AB']));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('comprobante', $result);
    }

    public function test_validateData_rejects_blank_comprobante(): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, ['comprobante' => '   ']));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('comprobante', $result);
    }

    public function test_validateData_accepts_comprobante_of_exactly_3_chars(): void
    {
        $this->assertTrue($this->purchase->validateData(array_merge($this->validData, ['comprobante' => 'ABC'])));
    }

    // -------------------------------------------------------------------------
    // Múltiples errores simultáneos
    // -------------------------------------------------------------------------

    public function test_validateData_collects_all_errors_at_once(): void
    {
        $result = $this->purchase->validateData([
            'id_producto'   => 0,
            'id_proveedor'  => 0,
            'nro_compra'    => 0,
            'fecha_compra'  => '',
            'comprobante'   => 'AB',
            'precio_compra' => 0,
            'cantidad'      => 0,
        ]);

        $this->assertIsArray($result);
        $this->assertCount(7, $result);
    }

    // -------------------------------------------------------------------------
    // Límites
    // -------------------------------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('limitsProvider')]
    public function test_validateData_limits(string $field, mixed $value, bool $valid): void
    {
        $result = $this->purchase->validateData(array_merge($this->validData, [$field => $value]));

        if ($valid) {
            $this->assertTrue($result);
        } else {
            $this->assertIsArray($result);
            $this->assertArrayHasKey($field, $result);
        }
    }

    public static function limitsProvider(): array
    {
        return [
            'precio mínimo válido'      => ['precio_compra', '0.01', true],
            'precio cero'               => ['precio_compra', '0', false],
            'precio máximo válido'      => ['precio_compra', '9999999.99', true],
            'precio sobre el máximo'    => ['precio_compra', '10000000', false],
            'cantidad mínima válida'    => ['cantidad', 1, true],
            'cantidad cero'             => ['cantidad', 0, false],
            'cantidad máxima válida'    => ['cantidad', 1000000, true],
            'cantidad sobre el máximo'  => ['cantidad', 1000001, false],
            'cantidad decimal'          => ['cantidad', '2.5', false],
            'fecha de hoy'              => ['fecha_compra', date('Y-m-d'), true],
            'fecha futura'              => ['fecha_compra', date('Y-m-d', strtotime('+1 day')), false],
            'fecha con formato inválido' => ['fecha_compra', '15/01/2026', false],
            'fecha inexistente'         => ['fecha_compra', '2026-02-30', false],
        ];
    }

    // -------------------------------------------------------------------------
    // nro_compra único
    // -------------------------------------------------------------------------

    private function seedPurchase(int $nro): int
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_proveedores (nombre_proveedor, celular, empresa, direccion) VALUES ('P', '7', 'E', 'D')");
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, fecha_ingreso, id_usuario, id_categoria) VALUES ('P001', 'A', 10, 15, 5, '2026-01-01', 1, 1)");
        $this->pdo->exec("INSERT INTO tb_compras (id_producto, nro_compra, fecha_compra, id_proveedor, comprobante, id_usuario, precio_compra, cantidad) VALUES (1, $nro, '2026-01-01', 1, 'FAC-001', 1, 10, 1)");

        return (int)$this->pdo->lastInsertId();
    }

    public function test_validateData_rejects_duplicate_nro_compra(): void
    {
        $this->seedPurchase(7);

        $result = $this->purchase->validateData(array_merge($this->validData, ['nro_compra' => 7]));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('nro_compra', $result);
    }

    public function test_validateData_allows_own_nro_compra_when_editing(): void
    {
        $id = $this->seedPurchase(7);

        $this->assertTrue($this->purchase->validateData(
            array_merge($this->validData, ['nro_compra' => 7, 'id_compra' => $id])
        ));
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\Purchase;
use Tests\TestCase;

final class PurchaseValidationTest extends TestCase
{
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
}

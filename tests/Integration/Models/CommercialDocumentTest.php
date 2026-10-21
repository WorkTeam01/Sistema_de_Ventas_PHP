<?php

namespace Tests\Integration\Models;

use App\Models\CommercialDocument;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class CommercialDocumentTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private CommercialDocument $documents;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->documents = new CommercialDocument();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Cliente Test', '12345678', '90000000', 'cliente@test.com')");
        $this->pdo->exec("INSERT INTO tb_proveedores
            (nombre_proveedor, celular, empresa, direccion)
            VALUES ('Proveedor Test', '90000000', 'Empresa Test', 'Dirección test')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P-00001', 'Producto Test', 12, 5.00, 10.00, '2026-01-01', 1, 1)");
    }

    private function quoteData(string $price = '10.00', string $status = 'emitida'): array
    {
        return [
            'estado' => $status,
            'fecha_documento' => '2026-02-01',
            'fecha_vencimiento' => '2026-02-15',
            'id_contacto' => 1,
            'observaciones' => 'Cotización de prueba',
            'usuario_nombre' => 'Admin',
            'items' => [
                'id_producto' => ['1'],
                'cantidad' => ['2'],
                'precio_unitario' => [$price],
            ],
        ];
    }

    public function test_createDocument_saves_quote_without_changing_stock_and_records_history(): void
    {
        $result = $this->documents->createDocument('cotizacion', $this->quoteData(), 1);

        $this->assertTrue($result['ok']);
        $document = $this->documents->findWithDetails((int)$result['id']);
        $this->assertSame('cotizacion', $document['tipo']);
        $this->assertCount(1, $document['items']);
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_documentos_comerciales_historial')->fetchColumn());
        $this->assertSame(12, (int)$this->pdo->query('SELECT stock FROM tb_almacen WHERE id_producto = 1')->fetchColumn());
    }

    public function test_createDocument_stores_blank_expiration_as_null(): void
    {
        $data = $this->quoteData();
        $data['fecha_vencimiento'] = '';

        $result = $this->documents->createDocument('cotizacion', $data, 1);

        $this->assertTrue($result['ok']);
        $this->assertNull(
            $this->pdo->query('SELECT fecha_vencimiento FROM tb_documentos_comerciales')->fetchColumn()
        );
    }

    public function test_createDocument_saves_purchase_order_without_changing_stock(): void
    {
        $data = $this->quoteData();
        $data['estado'] = 'emitida';

        $result = $this->documents->createDocument('orden_compra', $data, 1);
        $document = $this->documents->findWithDetails((int)$result['id']);

        $this->assertTrue($result['ok']);
        $this->assertSame('orden_compra', $document['tipo']);
        $this->assertNull($document['id_cliente']);
        $this->assertSame(1, (int)$document['id_proveedor']);
        $this->assertSame(12, (int)$this->pdo->query('SELECT stock FROM tb_almacen WHERE id_producto = 1')->fetchColumn());
    }

    public function test_validateData_rejects_fractional_quantities(): void
    {
        $data = $this->quoteData();
        $data['items']['cantidad'] = ['1.5'];

        $this->assertIsString($this->documents->validateData('cotizacion', $data));
    }

    public function test_updateDocument_keeps_previous_snapshot_in_history(): void
    {
        $created = $this->documents->createDocument('cotizacion', $this->quoteData(), 1);
        $updatedData = $this->quoteData('12.50', 'aceptada');
        $updatedData['observaciones'] = 'Cotización aceptada';

        $result = $this->documents->updateDocument((int)$created['id'], 'cotizacion', $updatedData, 1);
        $history = $this->documents->historyFor((int)$created['id']);

        $this->assertTrue($result['ok']);
        $this->assertCount(2, $history);
        $this->assertSame('corregir', $history[0]['accion']);
        $previous = json_decode($history[0]['datos_anteriores'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('emitida', $previous['estado']);
        $this->assertEqualsWithDelta(20.00, (float)$previous['total'], 0.001);
        $this->assertSame(12, (int)$this->pdo->query('SELECT stock FROM tb_almacen WHERE id_producto = 1')->fetchColumn());
    }

    public function test_allWithDetails_can_scope_documents_to_their_creator(): void
    {
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Vendedor', 'vendedor@test.com', 'hash', 1)");
        $first = $this->documents->createDocument('cotizacion', $this->quoteData(), 1);
        $second = $this->documents->createDocument('cotizacion', $this->quoteData(), 2);

        $visible = $this->documents->allWithDetails('cotizacion', 2);

        $this->assertSame([(int)$second['id']], array_map('intval', array_column($visible, 'id_documento')));
        $this->assertSame(2, (int)$visible[0]['id_usuario']);
        $this->assertTrue($first['ok']);
    }

    public function test_updateDocument_rolls_back_document_and_items_when_history_write_fails(): void
    {
        $created = $this->documents->createDocument('cotizacion', $this->quoteData(), 1);
        $this->pdo->exec(
            "CREATE TRIGGER reject_document_history
             BEFORE INSERT ON tb_documentos_comerciales_historial
             BEGIN
                 SELECT RAISE(ABORT, 'No se pudo guardar el historial');
             END"
        );
        $updatedData = $this->quoteData('12.50', 'aceptada');
        $updatedData['items']['cantidad'] = ['3'];

        $result = $this->documents->updateDocument((int)$created['id'], 'cotizacion', $updatedData, 1);
        $document = $this->documents->findWithDetails((int)$created['id']);

        $this->assertFalse($result['ok']);
        $this->assertSame('emitida', $document['estado']);
        $this->assertEqualsWithDelta(20.00, (float)$document['total'], 0.001);
        $this->assertSame(2, (int)$document['items'][0]['cantidad']);
        $this->assertEqualsWithDelta(10.00, (float)$document['items'][0]['precio_unitario'], 0.001);
        $this->assertSame(1, (int)$this->pdo->query(
            'SELECT COUNT(*) FROM tb_documentos_comerciales_historial'
        )->fetchColumn());
    }
}

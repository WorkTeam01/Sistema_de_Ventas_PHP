<?php

namespace Tests\Integration\Models;

use App\Models\CommercialDocument;
use PDO;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

final class CommercialDocumentConcurrencyMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private CommercialDocument $documents;
    private PDO $locker;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Administrador')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
            VALUES ('Admin', 'admin@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente)
            VALUES ('Cliente Test', '12345678', '90000000', 'cliente@test.com')");
        $this->pdo->exec("INSERT INTO tb_almacen
            (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
            VALUES ('P-00001', 'Producto Test', 12, 5.00, 10.00, CURDATE(), 1, 1)");

        $this->documents = new CommercialDocument();
        $result = $this->documents->createDocument('cotizacion', [
            'estado' => 'emitida',
            'fecha_documento' => date('Y-m-d'),
            'fecha_vencimiento' => '',
            'id_contacto' => 1,
            'observaciones' => 'Documento concurrente',
            'usuario_nombre' => 'Admin',
            'items' => [
                'id_producto' => ['1'],
                'cantidad' => ['1'],
                'precio_unitario' => ['10.00'],
            ],
        ], 1);
        self::assertTrue($result['ok']);

        $host = $_ENV['DB_TEST_HOST'] ?? getenv('DB_TEST_HOST');
        $port = $_ENV['DB_TEST_PORT'] ?? getenv('DB_TEST_PORT') ?: '3306';
        $user = $_ENV['DB_TEST_USER'] ?? getenv('DB_TEST_USER');
        $pass = $_ENV['DB_TEST_PASS'] ?? getenv('DB_TEST_PASS') ?: '';
        $database = $_ENV['DB_TEST_NAME'] ?? getenv('DB_TEST_NAME');
        $this->locker = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $this->pdo->exec('SET SESSION innodb_lock_wait_timeout = 1');
    }

    protected function tearDown(): void
    {
        if (isset($this->locker) && $this->locker->inTransaction()) {
            $this->locker->rollBack();
        }

        parent::tearDown();
    }

    public function test_update_waits_for_another_editor_and_can_succeed_after_lock_release(): void
    {
        $this->locker->beginTransaction();
        $this->locker->query(
            'SELECT id_documento FROM tb_documentos_comerciales WHERE id_documento = 1 FOR UPDATE'
        );

        $data = [
            'estado' => 'aceptada',
            'fecha_documento' => date('Y-m-d'),
            'fecha_vencimiento' => '',
            'id_contacto' => 1,
            'observaciones' => 'Documento actualizado',
            'usuario_nombre' => 'Admin',
            'items' => [
                'id_producto' => ['1'],
                'cantidad' => ['2'],
                'precio_unitario' => ['10.00'],
            ],
        ];

        $blocked = $this->documents->updateDocument(1, 'cotizacion', $data, 1);
        self::assertFalse($blocked['ok']);
        self::assertSame('emitida', $this->documents->findWithDetails(1)['estado']);

        $this->locker->rollBack();
        $updated = $this->documents->updateDocument(1, 'cotizacion', $data, 1);

        self::assertTrue($updated['ok']);
        self::assertSame('aceptada', $this->documents->findWithDetails(1)['estado']);
        self::assertSame(
            1,
            (int)$this->pdo->query(
                "SELECT COUNT(*) FROM tb_documentos_comerciales_historial WHERE accion = 'corregir'"
            )->fetchColumn()
        );
    }
}

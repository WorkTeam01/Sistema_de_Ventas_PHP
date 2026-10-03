<?php

namespace Tests\Integration\Models;

use App\Models\CartItem;
use PDO;
use PDOException;
use Tests\Concerns\RefreshMariaDatabase;
use Tests\TestCase;

/**
 * Las operaciones del carrito deben tomar el bloqueo de la reserva (el mismo que toma el cobro):
 * una segunda conexión que ya la tiene bloqueada, como un cobro en curso, hace esperar a la primera.
 */
final class CartItemConcurrencyMariaDbTest extends TestCase
{
    use RefreshMariaDatabase {
        setUp as setUpDatabase;
    }

    private CartItem $cart;
    private PDO $payer;
    private int $nro;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('A', 'a@t.com', 'x', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec(
            "INSERT INTO tb_almacen (codigo, nombre, descripcion, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
             VALUES ('P1', 'Prod', 'd', 50, 1, 2, CURDATE(), 1, 1)"
        );
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES (1)");
        $this->nro = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES ({$this->nro}, 1, 1)");

        $this->cart = new CartItem();
        $this->payer = $this->secondConnection();
        $this->pdo->exec('SET SESSION innodb_lock_wait_timeout = 1');
    }

    protected function tearDown(): void
    {
        if (isset($this->payer) && $this->payer->inTransaction()) {
            $this->payer->rollBack();
        }

        parent::tearDown();
    }

    private function secondConnection(): PDO
    {
        $host = $_ENV['DB_TEST_HOST'] ?? getenv('DB_TEST_HOST');
        $port = $_ENV['DB_TEST_PORT'] ?? getenv('DB_TEST_PORT') ?: '3306';
        $user = $_ENV['DB_TEST_USER'] ?? getenv('DB_TEST_USER');
        $pass = $_ENV['DB_TEST_PASS'] ?? getenv('DB_TEST_PASS') ?: '';

        return new PDO(
            "mysql:host=$host;port=$port;dbname={$this->mariaDbTestDatabase};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /** Simula un cobro en vuelo: otra conexión con la fila de la reserva bloqueada. */
    private function startPaymentInFlight(): void
    {
        $this->payer->beginTransaction();
        $this->payer->query("SELECT id_reserva FROM tb_pos_reservas WHERE id_reserva = {$this->nro} FOR UPDATE");
    }

    private function lines(): int
    {
        return (int)$this->payer->query("SELECT COALESCE(SUM(cantidad), 0) FROM tb_carrito WHERE nro_venta = {$this->nro}")->fetchColumn();
    }

    public function testAddItemWaitsForThePaymentInFlight(): void
    {
        $this->startPaymentInFlight();

        $this->expectException(PDOException::class);
        $this->expectExceptionMessageMatches('/lock wait timeout/i');
        try {
            $this->cart->addItem($this->nro, 1, 1, 1);
        } finally {
            $this->payer->rollBack();
            $this->assertSame(1, $this->lines(), 'la espera fallida no modifica el carrito');
        }
    }

    public function testRemoveItemWaitsForThePaymentInFlight(): void
    {
        $idCarrito = (int)$this->pdo->query('SELECT id_carrito FROM tb_carrito')->fetchColumn();
        $this->startPaymentInFlight();

        $this->expectException(PDOException::class);
        try {
            $this->cart->removeItem($idCarrito, 1);
        } finally {
            $this->payer->rollBack();
            $this->assertSame(1, $this->lines());
        }
    }

    public function testClearCartWaitsForThePaymentInFlight(): void
    {
        $this->startPaymentInFlight();

        $this->expectException(PDOException::class);
        try {
            $this->cart->clearCart($this->nro, 1);
        } finally {
            $this->payer->rollBack();
            $this->assertSame(1, $this->lines());
        }
    }

    public function testOperationsProceedOnceThePaymentEnds(): void
    {
        $this->startPaymentInFlight();
        $this->payer->rollBack();

        $this->assertTrue($this->cart->addItem($this->nro, 1, 2, 1)['ok']);
        $this->assertSame(3, $this->lines());
    }

    public function testAReleasedReservationIsRejectedAfterThePaymentCommits(): void
    {
        $this->startPaymentInFlight();
        $this->payer->exec("UPDATE tb_pos_reservas SET fyh_liberacion = NOW() WHERE id_reserva = {$this->nro}");
        $this->payer->commit();

        $this->assertSame('finalized', $this->cart->addItem($this->nro, 1, 1, 1)['error']);
        $this->assertSame(1, $this->lines(), 'no se escribe sobre una venta ya cobrada');
    }
}

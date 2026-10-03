<?php

namespace Tests\Integration\Models;

use App\Models\CartItem;
use App\Models\PosReservation;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class CartItemRepositoryTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private CartItem $cartItem;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->cartItem = new CartItem();
        $this->seedDependencies();
    }

    private function seedDependencies(): void
    {
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Vendedor', 'v@test.com', 'hash', 1), ('Otro', 'o@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente) VALUES ('Cliente A', '12345678', '70000001', 'cliente@test.com')");
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, fecha_ingreso, id_usuario, id_categoria) VALUES ('P001', 'Producto A', 5.00, 10.00, 50, '2026-01-01', 1, 1)");
    }

    private function openCart(int $userId = 1): int
    {
        return (new PosReservation())->openFor($userId);
    }

    private function cartQuantity(int $nroVenta, int $productId = 1): int
    {
        return (int)$this->pdo->query(
            "SELECT COALESCE(SUM(cantidad), 0) FROM tb_carrito WHERE nro_venta = $nroVenta AND id_producto = $productId"
        )->fetchColumn();
    }

    private function cartLineCount(int $nroVenta): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM tb_carrito WHERE nro_venta = $nroVenta")->fetchColumn();
    }

    // -------------------------------------------------------------------------
    // addItem
    // -------------------------------------------------------------------------

    public function test_addItem_adds_a_line_to_the_own_open_cart(): void
    {
        $nro = $this->openCart();

        $this->assertSame(['ok' => true, 'error' => null], $this->cartItem->addItem($nro, 1, 2, 1));
        $this->assertSame(2, $this->cartQuantity($nro));
    }

    public function test_addItem_accumulates_the_same_product_in_a_single_line(): void
    {
        $nro = $this->openCart();

        $this->cartItem->addItem($nro, 1, 2, 1);
        $this->cartItem->addItem($nro, 1, 3, 1);

        $this->assertSame(1, $this->cartLineCount($nro));
        $this->assertSame(5, $this->cartQuantity($nro));
    }

    public function test_addItem_validates_stock_against_the_total_already_in_the_cart(): void
    {
        $nro = $this->openCart();
        $this->cartItem->addItem($nro, 1, 40, 1);

        $result = $this->cartItem->addItem($nro, 1, 20, 1);

        $this->assertSame(['ok' => false, 'error' => 'stock'], $result);
        $this->assertSame(40, $this->cartQuantity($nro), 'no se modifica el carrito');
    }

    public function test_addItem_allows_reaching_exactly_the_stock(): void
    {
        $nro = $this->openCart();
        $this->cartItem->addItem($nro, 1, 40, 1);

        $this->assertTrue($this->cartItem->addItem($nro, 1, 10, 1)['ok']);
    }

    public function test_addItem_rejects_unknown_product_as_stock_error(): void
    {
        $nro = $this->openCart();

        $this->assertSame('stock', $this->cartItem->addItem($nro, 999, 1, 1)['error']);
    }

    public function test_addItem_rejects_non_positive_quantity(): void
    {
        $nro = $this->openCart();

        $this->assertSame('invalid_qty', $this->cartItem->addItem($nro, 1, 0, 1)['error']);
        $this->assertSame('invalid_qty', $this->cartItem->addItem($nro, 1, -3, 1)['error']);
        $this->assertSame(0, $this->cartLineCount($nro));
    }

    public function test_addItem_to_another_users_cart_is_forbidden(): void
    {
        $nro = $this->openCart(1);

        $result = $this->cartItem->addItem($nro, 1, 1, 2);

        $this->assertSame(['ok' => false, 'error' => 'forbidden'], $result);
        $this->assertSame(0, $this->cartLineCount($nro));
    }

    public function test_addItem_to_an_unreserved_number_is_forbidden(): void
    {
        $this->assertSame('forbidden', $this->cartItem->addItem(777, 1, 1, 1)['error']);
        $this->assertSame(0, $this->cartLineCount(777));
    }

    public function test_addItem_to_a_finalized_cart_is_rejected_without_changes(): void
    {
        $nro = $this->openCart();
        $this->cartItem->addItem($nro, 1, 1, 1);
        (new PosReservation())->release($nro);

        $result = $this->cartItem->addItem($nro, 1, 1, 1);

        $this->assertSame(['ok' => false, 'error' => 'finalized'], $result);
        $this->assertSame(1, $this->cartQuantity($nro));
    }

    public function test_addItem_to_another_users_finalized_cart_does_not_reveal_it(): void
    {
        $nro = $this->openCart(1);
        (new PosReservation())->release($nro);

        $this->assertSame('forbidden', $this->cartItem->addItem($nro, 1, 1, 2)['error']);
    }

    // -------------------------------------------------------------------------
    // removeItem
    // -------------------------------------------------------------------------

    public function test_removeItem_removes_a_line_of_the_own_cart(): void
    {
        $nro = $this->openCart();
        $this->cartItem->addItem($nro, 1, 1, 1);
        $idCarrito = (int)$this->pdo->query('SELECT id_carrito FROM tb_carrito')->fetchColumn();

        $this->assertSame(['ok' => true, 'error' => null], $this->cartItem->removeItem($idCarrito, 1));
        $this->assertSame(0, $this->cartLineCount($nro));
    }

    public function test_removeItem_of_another_users_line_is_forbidden(): void
    {
        $nro = $this->openCart(1);
        $this->cartItem->addItem($nro, 1, 1, 1);
        $idCarrito = (int)$this->pdo->query('SELECT id_carrito FROM tb_carrito')->fetchColumn();

        $result = $this->cartItem->removeItem($idCarrito, 2);

        $this->assertSame('forbidden', $result['error']);
        $this->assertSame(1, $this->cartLineCount($nro));
    }

    public function test_removeItem_of_a_line_that_no_longer_exists_is_a_harmless_noop(): void
    {
        // Doble clic en "quitar": la línea ya no existe y no pertenece a nadie, así que no se acusa acceso ajeno
        $nro = $this->openCart();
        $this->cartItem->addItem($nro, 1, 1, 1);
        $idCarrito = (int)$this->pdo->query('SELECT id_carrito FROM tb_carrito')->fetchColumn();

        $this->assertTrue($this->cartItem->removeItem($idCarrito, 1)['ok']);
        $this->assertSame(['ok' => true, 'error' => null], $this->cartItem->removeItem($idCarrito, 1));
        $this->assertSame(['ok' => true, 'error' => null], $this->cartItem->removeItem(999, 1));
    }

    public function test_removeItem_noop_does_not_open_the_door_to_other_users_lines(): void
    {
        $nro = $this->openCart(1);
        $this->cartItem->addItem($nro, 1, 1, 1);
        $idCarrito = (int)$this->pdo->query('SELECT id_carrito FROM tb_carrito')->fetchColumn();

        $this->assertSame('forbidden', $this->cartItem->removeItem($idCarrito, 2)['error']);
        $this->assertSame(1, $this->cartLineCount($nro));
    }

    public function test_removeItem_of_a_finalized_cart_is_rejected(): void
    {
        $nro = $this->openCart();
        $this->cartItem->addItem($nro, 1, 1, 1);
        $idCarrito = (int)$this->pdo->query('SELECT id_carrito FROM tb_carrito')->fetchColumn();
        (new PosReservation())->release($nro);

        $this->assertSame('finalized', $this->cartItem->removeItem($idCarrito, 1)['error']);
        $this->assertSame(1, $this->cartLineCount($nro));
    }

    // -------------------------------------------------------------------------
    // clearCart
    // -------------------------------------------------------------------------

    public function test_clearCart_empties_only_the_own_cart(): void
    {
        $mine = $this->openCart(1);
        $theirs = $this->openCart(2);
        $this->cartItem->addItem($mine, 1, 1, 1);
        $this->cartItem->addItem($theirs, 1, 1, 2);

        $this->assertTrue($this->cartItem->clearCart($mine, 1)['ok']);

        $this->assertSame(0, $this->cartLineCount($mine));
        $this->assertSame(1, $this->cartLineCount($theirs));
    }

    public function test_clearCart_of_another_users_cart_is_forbidden(): void
    {
        $nro = $this->openCart(1);
        $this->cartItem->addItem($nro, 1, 1, 1);

        $this->assertSame('forbidden', $this->cartItem->clearCart($nro, 2)['error']);
        $this->assertSame(1, $this->cartLineCount($nro));
    }

    public function test_clearCart_after_the_sale_is_finalized_or_cancelled_is_rejected(): void
    {
        $nro = $this->openCart();
        $this->cartItem->addItem($nro, 1, 1, 1);
        (new PosReservation())->release($nro);

        $this->assertSame('finalized', $this->cartItem->clearCart($nro, 1)['error']);
        $this->assertSame(1, $this->cartLineCount($nro), 'las líneas de una venta finalizada no se tocan');
    }

    // -------------------------------------------------------------------------
    // Transacción: cada operación es atómica y respeta una transacción externa
    // -------------------------------------------------------------------------

    public function test_operations_leave_no_transaction_open(): void
    {
        $nro = $this->openCart();

        $this->cartItem->addItem($nro, 1, 1, 1);
        $this->assertFalse($this->pdo->inTransaction());

        $this->cartItem->addItem($nro, 1, 999, 1); // rechazo por stock
        $this->assertFalse($this->pdo->inTransaction());

        $this->cartItem->addItem($nro, 1, 1, 2); // rechazo por dueño
        $this->assertFalse($this->pdo->inTransaction());

        $this->cartItem->clearCart($nro, 1);
        $this->assertFalse($this->pdo->inTransaction());
    }

    public function test_operations_join_an_outer_transaction_without_committing_it(): void
    {
        $nro = $this->openCart();

        $this->pdo->beginTransaction();
        $this->assertTrue($this->cartItem->addItem($nro, 1, 2, 1)['ok']);
        $this->assertTrue($this->pdo->inTransaction(), 'no cierra la transacción ajena');
        $this->pdo->rollBack();

        $this->assertSame(0, $this->cartLineCount($nro), 'el rollback externo deshace la escritura');
    }

    public function test_a_rejected_operation_inside_an_outer_transaction_does_not_roll_it_back(): void
    {
        $nro = $this->openCart(1);

        $this->pdo->beginTransaction();
        $this->assertSame('forbidden', $this->cartItem->addItem($nro, 1, 1, 2)['error']);
        $this->assertTrue($this->pdo->inTransaction());
        $this->pdo->rollBack();
    }
}

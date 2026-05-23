<?php

namespace Tests\Integration\Models;

use App\Models\CartItem;
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
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol) VALUES ('Vendedor', 'v@test.com', 'hash', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('General')");
        $this->pdo->exec("INSERT INTO tb_clientes (nombre_cliente, nit_ci_cliente, celular_cliente, email_cliente) VALUES ('Cliente A', '12345678', '70000001', 'cliente@test.com')");
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, precio_compra, precio_venta, stock, fecha_ingreso, id_usuario, id_categoria) VALUES ('P001', 'Producto A', 5.00, 10.00, 50, '2026-01-01', 1, 1)");
    }

    // -------------------------------------------------------------------------
    // purgeOrphans
    // -------------------------------------------------------------------------

    public function test_purgeOrphans_removes_abandoned_orphans(): void
    {
        // nro_venta = 1: huérfano abandonado (2 ítems)
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, 1, 2)");
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, 1, 1)");

        // nro_venta = 2: activo (excluido de la purga)
        $deleted = $this->cartItem->purgeOrphans(2);

        $this->assertSame(2, $deleted);
        $this->assertSame(0, $this->cartItem->countByNroVenta(1));
    }

    public function test_purgeOrphans_preserves_active_cart(): void
    {
        // nro_venta = 1: carrito activo (excluido de la purga)
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, 1, 3)");

        $deleted = $this->cartItem->purgeOrphans(1);

        $this->assertSame(0, $deleted);
        $this->assertSame(1, $this->cartItem->countByNroVenta(1));
    }

    public function test_purgeOrphans_preserves_cart_items_with_finalized_sale(): void
    {
        // nro_venta = 2 tiene venta finalizada → no debe borrarse
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (2, 1, 3)");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, total_pagado) VALUES (2, 1, 30.00)");

        $deleted = $this->cartItem->purgeOrphans(99);

        $this->assertSame(0, $deleted);
        $this->assertSame(1, $this->cartItem->countByNroVenta(2));
    }

    public function test_purgeOrphans_only_removes_abandoned_preserving_active_and_finalized(): void
    {
        // nro_venta = 1: huérfano abandonado (2 ítems)
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, 1, 1)");
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (1, 1, 2)");

        // nro_venta = 2: venta finalizada (1 ítem)
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (2, 1, 3)");
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_cliente, total_pagado) VALUES (2, 1, 30.00)");

        // nro_venta = 3: carrito activo (excluido)
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES (3, 1, 2)");

        $deleted = $this->cartItem->purgeOrphans(3);

        $this->assertSame(2, $deleted);
        $this->assertSame(0, $this->cartItem->countByNroVenta(1));
        $this->assertSame(1, $this->cartItem->countByNroVenta(2));
        $this->assertSame(1, $this->cartItem->countByNroVenta(3));
    }

    public function test_purgeOrphans_returns_zero_when_no_orphans(): void
    {
        $this->assertSame(0, $this->cartItem->purgeOrphans(99));
    }
}

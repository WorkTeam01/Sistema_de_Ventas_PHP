<?php

namespace Tests\Integration\Models;

use App\Models\PosReservation;
use Tests\Concerns\RefreshDatabase;
use Tests\TestCase;

final class PosReservationTest extends TestCase
{
    use RefreshDatabase {
        setUp as setUpDatabase;
    }

    private PosReservation $reservations;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->pdo->exec("INSERT INTO tb_roles (rol) VALUES ('Vendedor')");
        $this->pdo->exec("INSERT INTO tb_usuarios (nombres, email, password_user, id_rol)
                          VALUES ('A', 'a@t.com', 'x', 1), ('B', 'b@t.com', 'x', 1)");
        $this->pdo->exec("INSERT INTO tb_categorias (nombre_categoria) VALUES ('Cat')");
        $this->pdo->exec("INSERT INTO tb_almacen (codigo, nombre, stock, precio_compra, precio_venta, fecha_ingreso, id_usuario, id_categoria)
                          VALUES ('P1', 'Prod', 50, 5, 10, '2026-01-01', 1, 1)");

        $this->reservations = new PosReservation();
    }

    private function openReservation(int $userId): int
    {
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_usuario) VALUES ($userId)");

        return (int)$this->pdo->lastInsertId();
    }

    private function addCartLine(int $nroVenta): void
    {
        $this->pdo->exec("INSERT INTO tb_carrito (nro_venta, id_producto, cantidad) VALUES ($nroVenta, 1, 1)");
    }

    private function cartLines(int $nroVenta): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM tb_carrito WHERE nro_venta = $nroVenta")->fetchColumn();
    }

    private function isReleased(int $nroVenta): bool
    {
        return $this->pdo->query("SELECT fyh_liberacion FROM tb_pos_reservas WHERE id_reserva = $nroVenta")->fetchColumn() !== null;
    }

    public function testTwoUsersGetDifferentNumbersWithoutAddingItems(): void
    {
        $a = $this->reservations->openFor(1);
        $b = $this->reservations->openFor(2);

        $this->assertNotSame($a, $b);
    }

    public function testSameUserResumesTheirOpenReservation(): void
    {
        $first = $this->reservations->openFor(1);

        $this->assertSame($first, $this->reservations->openFor(1));
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pos_reservas')->fetchColumn());
    }

    public function testNumberStartsAboveSeededSequence(): void
    {
        $this->pdo->exec("INSERT INTO tb_pos_reservas (id_reserva, id_usuario, fyh_liberacion) VALUES (57, 2, CURRENT_TIMESTAMP)");

        $this->assertGreaterThan(57, $this->reservations->openFor(1));
    }

    public function testReleaseMarksTheReservationWithoutDeletingIt(): void
    {
        $nro = $this->reservations->openFor(1);

        $this->reservations->release($nro);

        $this->assertTrue($this->isReleased($nro));
        $this->assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM tb_pos_reservas')->fetchColumn());
    }

    public function testOpenForAfterReleaseCreatesANewNumber(): void
    {
        $first = $this->reservations->openFor(1);
        $this->reservations->release($first);

        $this->assertGreaterThan($first, $this->reservations->openFor(1));
    }

    public function testIsOpenForChecksOwnerAndState(): void
    {
        $nro = $this->reservations->openFor(1);

        $this->assertTrue($this->reservations->isOpenFor($nro, 1));
        $this->assertFalse($this->reservations->isOpenFor($nro, 2), 'reserva de otro usuario');
        $this->assertFalse($this->reservations->isOpenFor($nro + 100, 1), 'número inexistente');

        $this->reservations->release($nro);
        $this->assertFalse($this->reservations->isOpenFor($nro, 1), 'reserva liberada');
    }

    public function testDuplicateOwnReservationsAreReducedToTheMostRecent(): void
    {
        $old = $this->openReservation(1);
        $recent = $this->openReservation(1);

        $this->assertSame($recent, $this->reservations->openFor(1));
        $this->assertTrue($this->isReleased($old));
        $this->assertFalse($this->isReleased($recent));
    }

    public function testCleanupDeletesCartLinesOfReleasedDuplicatesOnly(): void
    {
        $old = $this->openReservation(1);
        $recent = $this->openReservation(1);
        $this->addCartLine($old);
        $this->addCartLine($recent);

        $this->reservations->openFor(1);

        $this->assertSame(0, $this->cartLines($old));
        $this->assertSame(1, $this->cartLines($recent), 'el carrito de la reserva conservada no se toca');
    }

    public function testCleanupKeepsLinesOfAnAlreadyFinalizedSale(): void
    {
        $old = $this->openReservation(1);
        $this->openReservation(1);
        $this->addCartLine($old);
        $this->pdo->exec("INSERT INTO tb_ventas (nro_venta, id_usuario, total_pagado) VALUES ($old, 1, 10)");

        $this->reservations->openFor(1);

        $this->assertSame(1, $this->cartLines($old));
    }

    public function testCleanupNeverTouchesAnotherUsersReservationsOrCart(): void
    {
        $otherReservation = $this->openReservation(2);
        $this->addCartLine($otherReservation);
        $this->openReservation(1);
        $this->openReservation(1);

        $this->reservations->openFor(1);

        $this->assertFalse($this->isReleased($otherReservation));
        $this->assertSame(1, $this->cartLines($otherReservation));
    }

    public function testOpeningDoesNotPurgeOtherUsersOrphanCarts(): void
    {
        // Antes se borraban los carritos sin venta de todos los vendedores al abrir el POS
        $other = $this->reservations->openFor(2);
        $this->addCartLine($other);

        $this->reservations->openFor(1);

        $this->assertSame(1, $this->cartLines($other));
    }
}

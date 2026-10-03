<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_carrito.
 * Gestiona los ítems del carrito de venta persistido en base de datos.
 */
class CartItem extends Model
{
    protected string $table      = 'tb_carrito';
    protected string $primaryKey = 'id_carrito';

    /**
     * Retorna todos los ítems del carrito para un número de venta dado,
     * con datos del producto (nombre, descripción, precio de venta, stock, imagen, código).
     *
     * @param int $nroVenta Número de venta.
     * @return array Lista de ítems con datos del producto.
     */
    public function getByNroVenta(int $nroVenta): array
    {
        return $this->query(
            "SELECT car.*, al.nombre, al.descripcion, al.stock, al.imagen, al.codigo,
                    COALESCE(car.precio_unitario, al.precio_venta) AS precio_venta
             FROM tb_carrito car
             INNER JOIN tb_almacen al ON car.id_producto = al.id_producto
             WHERE car.nro_venta = ?
             ORDER BY car.id_carrito ASC",
            [$nroVenta]
        );
    }

    /**
     * Agrega un producto al carrito de la reserva abierta del usuario. Si el producto ya está,
     * acumula la cantidad en esa línea y valida el stock contra el total resultante.
     *
     * @return array{ok: bool, error: ?string} error: forbidden|finalized|invalid_qty|stock
     */
    public function addItem(int $nroVenta, int $idProducto, int $cantidad, int $userId): array
    {
        return $this->withOpenReservation($nroVenta, $userId, function () use ($nroVenta, $idProducto, $cantidad): array {
            if ($cantidad < 1) {
                return ['ok' => false, 'error' => 'invalid_qty'];
            }

            $product = $this->query('SELECT stock FROM tb_almacen WHERE id_producto = ?', [$idProducto]);
            $lines = $this->query(
                'SELECT MIN(id_carrito) AS id_carrito, COALESCE(SUM(cantidad), 0) AS cantidad
                 FROM tb_carrito WHERE nro_venta = ? AND id_producto = ?',
                [$nroVenta, $idProducto]
            )[0];
            $inCart = (int)$lines['cantidad'];

            if (empty($product) || (int)$product[0]['stock'] < $inCart + $cantidad) {
                return ['ok' => false, 'error' => 'stock'];
            }

            if ($inCart > 0) {
                $this->db->prepare('UPDATE tb_carrito SET cantidad = cantidad + ? WHERE id_carrito = ?')
                    ->execute([$cantidad, (int)$lines['id_carrito']]);
            } else {
                $this->create(['nro_venta' => $nroVenta, 'id_producto' => $idProducto, 'cantidad' => $cantidad]);
            }

            return ['ok' => true, 'error' => null];
        });
    }

    /**
     * Elimina una línea del carrito si pertenece a la reserva abierta del usuario. Quitar una línea que
     * ya no existe (doble clic) es un no-op exitoso: sin dueño no hay nada ajeno que proteger.
     *
     * @return array{ok: bool, error: ?string} error: forbidden|finalized
     */
    public function removeItem(int $idCarrito, int $userId): array
    {
        $line = $this->find($idCarrito);
        if ($line === false) {
            return ['ok' => true, 'error' => null];
        }

        return $this->withOpenReservation((int)$line['nro_venta'], $userId, function () use ($idCarrito): array {
            $this->delete($idCarrito);

            return ['ok' => true, 'error' => null];
        });
    }

    /**
     * Vacía el carrito de la reserva abierta del usuario (cancelar una venta en curso).
     *
     * @return array{ok: bool, error: ?string} error: forbidden|finalized
     */
    public function clearCart(int $nroVenta, int $userId): array
    {
        return $this->withOpenReservation($nroVenta, $userId, function () use ($nroVenta): array {
            $this->db->prepare('DELETE FROM tb_carrito WHERE nro_venta = ?')->execute([$nroVenta]);

            return ['ok' => true, 'error' => null];
        });
    }

    /**
     * Ejecuta $write con la reserva del usuario abierta y bloqueada hasta el fin de la transacción.
     * El bloqueo (el mismo que toma Sale::storeWithStock al cobrar) serializa la escritura con un
     * cobro o cancelación simultáneos: sin él, la reserva podía liberarse entre la comprobación y la
     * escritura y las líneas de una venta ya cobrada se modificaban. Si ya hay una transacción
     * externa en curso se une a ella.
     *
     * @param callable(): array{ok: bool, error: ?string} $write
     * @return array{ok: bool, error: ?string}
     */
    private function withOpenReservation(int $nroVenta, int $userId, callable $write): array
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {
            if (!(new PosReservation())->isOpenFor($nroVenta, $userId, true)) {
                if ($ownsTransaction) {
                    $this->db->rollBack();
                }

                return ['ok' => false, 'error' => $this->rejectionReason($nroVenta, $userId)];
            }

            $result = $write();

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return $result;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Motivo por el que la reserva no está abierta para el usuario. "finalized" solo se informa al dueño
     * de una reserva ya liberada (cobrada o cancelada); cualquier otro caso es "forbidden" para no
     * revelar qué números existen.
     */
    private function rejectionReason(int $nroVenta, int $userId): string
    {
        $row = $this->query('SELECT id_usuario FROM tb_pos_reservas WHERE id_reserva = ?', [$nroVenta]);

        return !empty($row) && (int)$row[0]['id_usuario'] === $userId ? 'finalized' : 'forbidden';
    }

    /**
     * Cuenta los ítems del carrito para un número de venta dado.
     * Útil para validar que el carrito no está vacío antes de finalizar la venta.
     *
     * @param int $nroVenta Número de venta.
     * @return int Número de ítems en el carrito.
     */
    public function countByNroVenta(int $nroVenta): int
    {
        $rows = $this->query(
            "SELECT COUNT(*) AS total FROM tb_carrito WHERE nro_venta = ?",
            [$nroVenta]
        );
        return (int) ($rows[0]['total'] ?? 0);
    }
}

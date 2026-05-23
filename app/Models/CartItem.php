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
            "SELECT car.*, al.nombre, al.descripcion, al.precio_venta, al.stock, al.imagen, al.codigo
             FROM tb_carrito car
             INNER JOIN tb_almacen al ON car.id_producto = al.id_producto
             WHERE car.nro_venta = ?
             ORDER BY car.id_carrito ASC",
            [$nroVenta]
        );
    }

    /**
     * Agrega un producto al carrito, verificando previamente que hay stock suficiente.
     *
     * @param int $nroVenta   Número de venta.
     * @param int $idProducto ID del producto.
     * @param int $cantidad   Cantidad solicitada.
     * @return bool true si se insertó, false si no hay stock suficiente.
     */
    public function addItem(int $nroVenta, int $idProducto, int $cantidad): bool
    {
        // Verificar stock disponible
        $rows = $this->query(
            "SELECT stock FROM tb_almacen WHERE id_producto = ?",
            [$idProducto]
        );
        if (empty($rows) || (int) $rows[0]['stock'] < $cantidad) {
            return false;
        }

        $this->create([
            'nro_venta'   => $nroVenta,
            'id_producto' => $idProducto,
            'cantidad'    => $cantidad,
        ]);
        return true;
    }

    /**
     * Elimina un ítem del carrito por su PK.
     *
     * @param int $idCarrito ID del ítem de carrito a eliminar.
     * @return bool true si se eliminó.
     */
    public function removeItem(int $idCarrito): bool
    {
        return $this->delete($idCarrito);
    }

    /**
     * Elimina todos los ítems del carrito para un nro_venta dado.
     * Se usa al cancelar explícitamente una venta en curso.
     *
     * @param int $nroVenta Número de venta a limpiar.
     * @return int Número de filas eliminadas.
     */
    public function clearCart(int $nroVenta): int
    {
        $stmt = $this->db->prepare('DELETE FROM tb_carrito WHERE nro_venta = ?');
        $stmt->execute([$nroVenta]);
        return $stmt->rowCount();
    }

    /**
     * Elimina carritos huérfanos: filas en tb_carrito cuyo nro_venta
     * no tiene venta finalizada en tb_ventas (el usuario abandonó el POS).
     * El nro_venta activo se excluye para no borrar el carrito en construcción.
     *
     * @param int $excludeNroVenta nro_venta activo que no debe purgarse.
     * @return int Número de filas eliminadas.
     */
    public function purgeOrphans(int $excludeNroVenta): int
    {
        $stmt = $this->db->prepare(
            'DELETE FROM tb_carrito
             WHERE nro_venta NOT IN (SELECT nro_venta FROM tb_ventas)
               AND nro_venta != ?'
        );
        $stmt->execute([$excludeNroVenta]);
        return $stmt->rowCount();
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

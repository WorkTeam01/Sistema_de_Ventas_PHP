<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_compras.
 *
 * Hereda find(), count() de Model.
 * Las operaciones de escritura son transaccionales porque afectan tb_almacen (stock).
 */
class Purchase extends Model
{
    protected string $table = 'tb_compras';
    protected string $primaryKey = 'id_compra';

    /**
     * Retorna todas las compras con datos de producto, proveedor y usuario.
     *
     * @return array Lista de compras con datos relacionados.
     */
    public function allWithDetails(): array
    {
        return $this->query(
            "SELECT co.*, al.codigo, al.nombre AS nombre_producto, al.imagen,
                    pro.nombre_proveedor, us.email AS email_usuario
             FROM tb_compras co
             INNER JOIN tb_almacen al ON co.id_producto = al.id_producto
             INNER JOIN tb_proveedores pro ON co.id_proveedor = pro.id_proveedor
             INNER JOIN tb_usuarios us ON co.id_usuario = us.id_usuario
             ORDER BY co.id_compra DESC"
        );
    }

    /**
     * Retorna el detalle completo de una compra con todos los datos relacionados.
     *
     * @param int $id ID de la compra.
     * @return array|null Fila de compra o null si no existe.
     */
    public function findWithDetails(int $id): ?array
    {
        $rows = $this->query(
            "SELECT co.*, al.codigo, al.nombre AS nombre_producto,
                    al.imagen, al.stock, al.stock_minimo, al.stock_maximo,
                    al.precio_compra AS precio_compra_producto, al.precio_venta,
                    al.descripcion AS descripcion_producto,
                    pro.nombre_proveedor, pro.empresa,
                    pro.celular, pro.telefono, pro.email AS email_proveedor,
                    pro.direccion, us.email AS email_usuario,
                    cat.nombre_categoria
             FROM tb_compras co
             INNER JOIN tb_almacen al ON co.id_producto = al.id_producto
             INNER JOIN tb_proveedores pro ON co.id_proveedor = pro.id_proveedor
             INNER JOIN tb_usuarios us ON co.id_usuario = us.id_usuario
             INNER JOIN tb_categorias cat ON al.id_categoria = cat.id_categoria
             WHERE co.id_compra = ?",
            [$id]
        );
        return $rows[0] ?? null;
    }

    /**
     * Retorna el siguiente número de compra (count + 1).
     *
     * @return int Número a mostrar en el formulario.
     */
    public function nextNumber(): int
    {
        return $this->count() + 1;
    }

    /**
     * Inserta una compra y actualiza el stock del producto en una transacción.
     *
     * @param array $data Datos de la compra.
     * @return bool true si la transacción se completó, false si hubo error.
     */
    public function storeWithStock(array $data): bool
    {
        $db = $this->db;
        try {
            $db->beginTransaction();
            $db->prepare(
                "INSERT INTO tb_compras
                 (id_producto, nro_compra, fecha_compra, id_proveedor,
                  comprobante, id_usuario, precio_compra, cantidad)
                 VALUES (?,?,?,?,?,?,?,?)"
            )->execute([
                $data['id_producto'],
                $data['nro_compra'],
                $data['fecha_compra'],
                $data['id_proveedor'],
                $data['comprobante'],
                $data['id_usuario'],
                $data['precio_compra'],
                $data['cantidad'],
            ]);
            $db->prepare(
                "UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ?"
            )->execute([$data['cantidad'], $data['id_producto']]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Actualiza una compra y ajusta el stock del producto en una transacción.
     *
     * Si el producto cambia, revierte el stock del producto viejo y suma al nuevo.
     * Si el producto no cambia, aplica el ajuste neto (nueva_cantidad - antigua_cantidad).
     *
     * @param array $data Datos nuevos de la compra (incluye id_compra).
     * @param int $oldProductId ID del producto antes de la edición.
     * @param int $oldCantidad Cantidad antes de la edición.
     * @return bool true si la transacción se completó, false si hubo error.
     */
    public function updateWithStock(array $data, int $oldProductId, int $oldCantidad): bool
    {
        $db = $this->db;
        try {
            $db->beginTransaction();
            $db->prepare(
                "UPDATE tb_compras
                 SET id_producto=?, nro_compra=?, fecha_compra=?, id_proveedor=?,
                     comprobante=?, id_usuario=?, precio_compra=?, cantidad=?
                 WHERE id_compra=?"
            )->execute([
                $data['id_producto'],
                $data['nro_compra'],
                $data['fecha_compra'],
                $data['id_proveedor'],
                $data['comprobante'],
                $data['id_usuario'],
                $data['precio_compra'],
                $data['cantidad'],
                $data['id_compra'],
            ]);
            if ($data['id_producto'] === $oldProductId) {
                $diff = $data['cantidad'] - $oldCantidad;
                $db->prepare(
                    "UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ?"
                )->execute([$diff, $data['id_producto']]);
            } else {
                $db->prepare(
                    "UPDATE tb_almacen SET stock = stock - ? WHERE id_producto = ?"
                )->execute([$oldCantidad, $oldProductId]);
                $db->prepare(
                    "UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ?"
                )->execute([$data['cantidad'], $data['id_producto']]);
            }
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Elimina una compra y revierte el stock del producto en una transacción.
     *
     * @param int $id ID de la compra.
     * @param int $idProducto ID del producto afectado.
     * @param int $cantidad Cantidad a revertir del stock.
     * @return bool true si la transacción se completó, false si hubo error.
     */
    /** Suma de compras del mes actual (precio_compra * cantidad por fila). */
    public function totalCurrentMonth(): float
    {
        $rows = $this->query(
            "SELECT COALESCE(SUM(precio_compra * cantidad), 0) AS total
             FROM tb_compras
             WHERE YEAR(fyh_creacion) = YEAR(CURDATE())
               AND MONTH(fyh_creacion) = MONTH(CURDATE())"
        );
        return (float)$rows[0]['total'];
    }

    /** Suma de compras del mes anterior. */
    public function totalPreviousMonth(): float
    {
        $rows = $this->query(
            "SELECT COALESCE(SUM(precio_compra * cantidad), 0) AS total
             FROM tb_compras
             WHERE YEAR(fyh_creacion) = YEAR(CURDATE() - INTERVAL 1 MONTH)
               AND MONTH(fyh_creacion) = MONTH(CURDATE() - INTERVAL 1 MONTH)"
        );
        return (float)$rows[0]['total'];
    }

    /**
     * Compras agrupadas por mes — últimos N meses.
     *
     * @return array Lista de ['mes' => 'YYYY-MM', 'total' => float]
     */
    public function totalsByMonth(int $months = 6): array
    {
        $interval = $months - 1;
        return $this->query(
            "SELECT DATE_FORMAT(fyh_creacion, '%Y-%m') AS mes,
                    COALESCE(SUM(precio_compra * cantidad), 0) AS total
             FROM tb_compras
             WHERE fyh_creacion >= DATE_FORMAT(CURDATE() - INTERVAL $interval MONTH, '%Y-%m-01')
             GROUP BY DATE_FORMAT(fyh_creacion, '%Y-%m')
             ORDER BY mes ASC"
        );
    }

    public function destroyWithStock(int $id, int $idProducto, int $cantidad): bool
    {
        $db = $this->db;
        try {
            $db->beginTransaction();
            $db->prepare(
                "DELETE FROM tb_compras WHERE id_compra = ?"
            )->execute([$id]);
            $db->prepare(
                "UPDATE tb_almacen SET stock = stock - ? WHERE id_producto = ?"
            )->execute([$cantidad, $idProducto]);
            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Valida los datos de una compra.
     *
     * @param array $data Datos a validar.
     * @return bool|array true si válidos, array de errores si no.
     */
    public function validateData(array $data): bool|array
    {
        $errors = [];

        if (empty($data['id_producto']) || (int)$data['id_producto'] <= 0) {
            $errors['id_producto'] = 'Producto requerido.';
        }
        if (empty($data['id_proveedor']) || (int)$data['id_proveedor'] <= 0) {
            $errors['id_proveedor'] = 'Proveedor requerido.';
        }
        if (empty($data['nro_compra']) || (int)$data['nro_compra'] <= 0) {
            $errors['nro_compra'] = 'Número de compra requerido.';
        }
        if (empty($data['fecha_compra'])) {
            $errors['fecha_compra'] = 'Fecha requerida.';
        }
        if (empty($data['comprobante'])) {
            $errors['comprobante'] = 'Comprobante requerido.';
        }
        if (!is_numeric($data['precio_compra'] ?? '')) {
            $errors['precio_compra'] = 'Precio debe ser numérico.';
        }
        if (!is_numeric($data['cantidad'] ?? '') || (int)$data['cantidad'] < 1) {
            $errors['cantidad'] = 'Cantidad debe ser un número mayor a cero.';
        }

        return empty($errors) ? true : $errors;
    }
}

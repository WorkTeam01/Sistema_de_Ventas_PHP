<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_ventas.
 *
 * Las operaciones de escritura son transaccionales porque afectan tb_almacen (stock)
 * y tb_carrito. El orden de eliminación es crítico: tb_ventas (hija) antes de tb_carrito
 * (referenciada), debido a la FK tb_ventas.nro_venta → tb_carrito.nro_venta.
 */
class Sale extends Model
{
    protected string $table      = 'tb_ventas';
    protected string $primaryKey = 'id_venta';

    /**
     * Retorna todas las ventas con datos del cliente, ordenadas por id_venta DESC.
     *
     * @return array Lista de ventas con nombre y NIT/CI del cliente.
     */
    public function allWithDetails(): array
    {
        return $this->query(
            "SELECT v.*, c.nombre_cliente, c.nit_ci_cliente
             FROM tb_ventas v
             INNER JOIN tb_clientes c ON v.id_cliente = c.id_cliente
             ORDER BY v.id_venta DESC"
        );
    }

    /**
     * Retorna el detalle completo de una venta: datos de la venta, cliente e ítems del carrito.
     *
     * @param int $id ID de la venta.
     * @return array|null Array con claves de la venta + 'items' (array de ítems), o null si no existe.
     */
    public function findWithDetails(int $id): ?array
    {
        $rows = $this->query(
            "SELECT v.*, c.nombre_cliente, c.nit_ci_cliente, c.celular_cliente,
                    c.email_cliente, c.id_cliente AS id_cliente_rel
             FROM tb_ventas v
             INNER JOIN tb_clientes c ON v.id_cliente = c.id_cliente
             WHERE v.id_venta = ?",
            [$id]
        );

        if (empty($rows)) {
            return null;
        }

        $sale  = $rows[0];
        $items = $this->query(
            "SELECT car.*, al.nombre, al.descripcion, al.precio_venta, al.stock, al.imagen, al.codigo
             FROM tb_carrito car
             INNER JOIN tb_almacen al ON car.id_producto = al.id_producto
             WHERE car.nro_venta = ?
             ORDER BY car.id_carrito ASC",
            [$sale['nro_venta']]
        );

        $sale['items'] = $items;
        return $sale;
    }

    /**
     * Retorna el siguiente número de venta usando MAX para evitar reutilizar números
     * tras eliminaciones.
     *
     * @return int Siguiente número de venta.
     */
    public function nextNumber(): int
    {
        $rows = $this->query("SELECT COALESCE(MAX(nro_venta), 0) + 1 AS next FROM tb_ventas");
        return (int) ($rows[0]['next'] ?? 1);
    }

    /**
     * Inserta la cabecera de venta y decrementa el stock de cada producto del carrito,
     * todo en una sola transacción.
     *
     * @param array $data      Datos de la venta: nro_venta, id_cliente, total_pagado.
     * @return bool true si la transacción se completó, false si hubo error.
     */
    public function storeWithStock(array $data): bool
    {
        $db = $this->db;
        try {
            $db->beginTransaction();

            // Verificar carrito no vacío
            $stmt = $db->prepare("SELECT COUNT(*) FROM tb_carrito WHERE nro_venta = ?");
            $stmt->execute([$data['nro_venta']]);
            if ((int) $stmt->fetchColumn() === 0) {
                $db->rollBack();
                return false;
            }

            // INSERT cabecera de venta
            $db->prepare(
                "INSERT INTO tb_ventas (nro_venta, id_cliente, total_pagado) VALUES (?, ?, ?)"
            )->execute([
                $data['nro_venta'],
                $data['id_cliente'],
                $data['total_pagado'],
            ]);

            // Decrementar stock de cada ítem del carrito
            $items = $db->prepare(
                "SELECT id_producto, cantidad FROM tb_carrito WHERE nro_venta = ?"
            );
            $items->execute([$data['nro_venta']]);
            $updateStock = $db->prepare(
                "UPDATE tb_almacen SET stock = stock - ? WHERE id_producto = ?"
            );
            foreach ($items->fetchAll(\PDO::FETCH_ASSOC) as $item) {
                $updateStock->execute([$item['cantidad'], $item['id_producto']]);
            }

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    /**
     * Elimina una venta revirtiendo el stock y borrando registros en el orden correcto:
     * 1. Revertir stock (UPDATE tb_almacen)
     * 2. DELETE tb_ventas (tabla hija: tb_ventas.nro_venta REFERENCES tb_carrito.nro_venta)
     * 3. DELETE tb_carrito (tabla referenciada)
     *
     * @param int $id ID de la venta a eliminar.
     * @return bool true si la transacción se completó, false si hubo error.
     */
    public function destroyWithStock(int $id): bool
    {
        $db = $this->db;
        try {
            $db->beginTransaction();

            // Obtener nro_venta
            $stmt = $db->prepare("SELECT nro_venta FROM tb_ventas WHERE id_venta = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                $db->rollBack();
                return false;
            }
            $nroVenta = (int) $row['nro_venta'];

            // Obtener ítems del carrito para revertir stock
            $items = $db->prepare(
                "SELECT id_producto, cantidad FROM tb_carrito WHERE nro_venta = ?"
            );
            $items->execute([$nroVenta]);
            $revertStock = $db->prepare(
                "UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ?"
            );
            foreach ($items->fetchAll(\PDO::FETCH_ASSOC) as $item) {
                $revertStock->execute([$item['cantidad'], $item['id_producto']]);
            }

            // DELETE tb_ventas PRIMERO (es la tabla hija)
            $db->prepare("DELETE FROM tb_ventas WHERE id_venta = ?")->execute([$id]);

            // DELETE tb_carrito DESPUÉS (es la referenciada)
            $db->prepare("DELETE FROM tb_carrito WHERE nro_venta = ?")->execute([$nroVenta]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }
}

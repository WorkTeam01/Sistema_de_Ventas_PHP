<?php

namespace App\Models;

use App\Core\Auth;
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
    protected string $table = 'tb_ventas';
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

        $sale = $rows[0];
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
        $rows = $this->query(
            "SELECT COALESCE(MAX(n), 0) + 1 AS next FROM (
                SELECT MAX(nro_venta) AS n FROM tb_ventas
                UNION ALL
                SELECT MAX(nro_venta) AS n FROM tb_carrito
            ) sub"
        );
        return (int)($rows[0]['next'] ?? 1);
    }

    /**
     * Inserta la cabecera de venta y decrementa el stock de cada producto del carrito,
     * todo en una sola transacción.
     *
     * @param array $data Datos de la venta: nro_venta, id_cliente, total_pagado.
     * @return bool true si la transacción se completó, false si hubo error.
     */
    public function storeWithStock(array $data): int|false
    {
        $db = $this->db;
        try {
            $db->beginTransaction();

            // Verificar carrito no vacío
            $stmt = $db->prepare("SELECT COUNT(*) FROM tb_carrito WHERE nro_venta = ?");
            $stmt->execute([$data['nro_venta']]);
            if ((int)$stmt->fetchColumn() === 0) {
                $db->rollBack();
                return false;
            }

            // Calcular total real desde los precios actuales del catálogo
            $totalsStmt = $db->prepare(
                "SELECT SUM(car.cantidad * al.precio_venta) AS total
                 FROM tb_carrito car
                 JOIN tb_almacen al ON al.id_producto = car.id_producto
                 WHERE car.nro_venta = ?"
            );
            $totalsStmt->execute([$data['nro_venta']]);
            $totalReal = (float)($totalsStmt->fetchColumn() ?? 0.0);

            // INSERT cabecera de venta
            $idUsuario = Auth::user()['id_usuario'] ?? null;
            $db->prepare(
                "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado) VALUES (?, ?, ?, ?)"
            )->execute([
                $data['nro_venta'],
                $data['id_cliente'],
                $idUsuario,
                $totalReal,
            ]);
            $idVenta = (int)$db->lastInsertId();

            // Decrementar stock de cada ítem — la cláusula AND stock >= ? previene stock negativo
            $items = $db->prepare(
                "SELECT id_producto, cantidad FROM tb_carrito WHERE nro_venta = ?"
            );
            $items->execute([$data['nro_venta']]);
            $updateStock = $db->prepare(
                "UPDATE tb_almacen SET stock = stock - ? WHERE id_producto = ? AND stock >= ?"
            );
            foreach ($items->fetchAll(\PDO::FETCH_ASSOC) as $item) {
                $updateStock->execute([$item['cantidad'], $item['id_producto'], $item['cantidad']]);
                if ($updateStock->rowCount() === 0) {
                    $db->rollBack();
                    return false;
                }
            }

            $db->commit();
            return $idVenta;
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
    /** Suma de ventas del mes actual. */
    public function totalCurrentMonth(): float
    {
        $rows = $this->query(
            "SELECT COALESCE(SUM(total_pagado), 0) AS total
             FROM tb_ventas
             WHERE YEAR(fyh_creacion) = YEAR(CURDATE())
               AND MONTH(fyh_creacion) = MONTH(CURDATE())"
        );
        return (float)$rows[0]['total'];
    }

    /** Suma de ventas del mes anterior. */
    public function totalPreviousMonth(): float
    {
        $rows = $this->query(
            "SELECT COALESCE(SUM(total_pagado), 0) AS total
             FROM tb_ventas
             WHERE YEAR(fyh_creacion) = YEAR(CURDATE() - INTERVAL 1 MONTH)
               AND MONTH(fyh_creacion) = MONTH(CURDATE() - INTERVAL 1 MONTH)"
        );
        return (float)$rows[0]['total'];
    }

    /**
     * Cantidad y monto de ventas de hoy.
     *
     * @return array{cantidad: int, monto: float}
     */
    public function todaySummary(): array
    {
        $rows = $this->query(
            "SELECT COUNT(*) AS cantidad, COALESCE(SUM(total_pagado), 0) AS monto
             FROM tb_ventas
             WHERE DATE(fyh_creacion) = CURDATE()"
        );
        return [
            'cantidad' => (int)$rows[0]['cantidad'],
            'monto' => (float)$rows[0]['monto'],
        ];
    }

    /**
     * Ventas agrupadas por mes — últimos N meses.
     *
     * @return array Lista de ['mes' => 'YYYY-MM', 'total' => float]
     */
    public function totalsByMonth(int $months = 6): array
    {
        $interval = (int)($months - 1);
        return $this->query(
            "SELECT DATE_FORMAT(fyh_creacion, '%Y-%m') AS mes,
                    COALESCE(SUM(total_pagado), 0) AS total
             FROM tb_ventas
             WHERE fyh_creacion >= DATE_FORMAT(CURDATE() - INTERVAL $interval MONTH, '%Y-%m-01')
             GROUP BY DATE_FORMAT(fyh_creacion, '%Y-%m')
             ORDER BY mes ASC"
        );
    }

    /** Últimas N ventas con nombre del cliente. */
    public function latest(int $limit = 5): array
    {
        $limit = (int)$limit;
        return $this->query(
            "SELECT v.id_venta, v.nro_venta, c.nombre_cliente, v.total_pagado, v.fyh_creacion
             FROM tb_ventas v
             INNER JOIN tb_clientes c ON v.id_cliente = c.id_cliente
             ORDER BY v.id_venta DESC
             LIMIT $limit"
        );
    }

    /**
     * Calcula los totales derivados de los ítems de una venta para la factura.
     *
     * @param array $items Ítems del carrito (deben incluir 'cantidad' y 'precio_venta').
     * @return array{precio_total: float, cantidad_total: int, total_unitarios: float}
     */
    public function computeInvoiceTotals(array $items): array
    {
        $precioTotal = 0.0;
        $cantidadTotal = 0;
        $totalUnitarios = 0.0;

        foreach ($items as $item) {
            $cantidad = (int)$item['cantidad'];
            $precioUnitario = (float)$item['precio_venta'];
            $precioTotal += $cantidad * $precioUnitario;
            $cantidadTotal += $cantidad;
            $totalUnitarios += $precioUnitario;
        }

        return [
            'precio_total' => $precioTotal,
            'cantidad_total' => $cantidadTotal,
            'total_unitarios' => $totalUnitarios,
        ];
    }

    /**
     * Agrega el subtotal (cantidad * precio_venta) a cada ítem, para uso directo en vistas.
     *
     * @param array $items Ítems del carrito/venta (deben incluir 'cantidad' y 'precio_venta').
     * @return array Ítems con la clave 'subtotal' añadida.
     */
    public function withSubtotals(array $items): array
    {
        return array_map(static function (array $item): array {
            $item['subtotal'] = (float)$item['cantidad'] * (float)$item['precio_venta'];
            return $item;
        }, $items);
    }

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
            $nroVenta = (int)$row['nro_venta'];

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

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
     * Filtra por usuario si se indica $userId.
     *
     * @return array Lista de ventas con nombre y NIT/CI del cliente.
     */
    public function allWithDetails(?int $userId = null): array
    {
        $sql = "SELECT v.*, c.nombre_cliente, c.nit_ci_cliente,
                       EXISTS(
                           SELECT 1 FROM tb_devoluciones d
                           WHERE d.id_venta = v.id_venta
                       ) AS tiene_devoluciones
                FROM tb_ventas v
                INNER JOIN tb_clientes c ON v.id_cliente = c.id_cliente";
        $params = [];
        if ($userId !== null) {
            $sql .= " WHERE v.id_usuario = ?";
            $params[] = $userId;
        }
        $sql .= " ORDER BY v.id_venta DESC";
        return $this->query($sql, $params);
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
            "SELECT car.*, al.nombre, al.descripcion, al.stock, al.imagen, al.codigo,
                    COALESCE(car.precio_unitario, al.precio_venta) AS precio_venta
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

            // Persistir precio de venta actual en cada ítem del carrito
            $persistPrice = $db->prepare(
                "UPDATE tb_carrito SET precio_unitario = (SELECT precio_venta FROM tb_almacen WHERE id_producto = tb_carrito.id_producto) WHERE nro_venta = ?"
            );
            $persistPrice->execute([$data['nro_venta']]);

            // Calcular total desde precios congelados
            $totalsStmt = $db->prepare(
                "SELECT SUM(cantidad * precio_unitario) AS total
                 FROM tb_carrito WHERE nro_venta = ?"
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
    /** Suma de ventas del mes actual. Filtra por usuario si se indica $userId. */
    public function totalCurrentMonth(?int $userId = null): float
    {
        $isSqlite = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $period = $isSqlite
            ? "strftime('%Y-%m', fyh_creacion) = strftime('%Y-%m', 'now')"
            : "YEAR(fyh_creacion) = YEAR(CURDATE()) AND MONTH(fyh_creacion) = MONTH(CURDATE())";
        $returnPeriod = str_replace('fyh_creacion', 'dv.fyh_creacion', $period);
        $sql = "SELECT
                    (SELECT COALESCE(SUM(v.total_pagado), 0) FROM tb_ventas v WHERE $period"
            . ($userId !== null ? " AND v.id_usuario = ?" : '') . ")
                    - (SELECT COALESCE(SUM(dv.monto), 0)
                       FROM tb_devoluciones dv
                       INNER JOIN tb_ventas v2 ON v2.id_venta = dv.id_venta
                       WHERE $returnPeriod"
            . ($userId !== null ? " AND v2.id_usuario = ?" : '') . ") AS total";
        $params = $userId !== null ? [$userId, $userId] : [];
        $rows = $this->query($sql, $params);
        return (float)$rows[0]['total'];
    }

    /** Suma de ventas del mes anterior. Filtra por usuario si se indica $userId. */
    public function totalPreviousMonth(?int $userId = null): float
    {
        $isSqlite = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $period = $isSqlite
            ? "strftime('%Y-%m', fyh_creacion) = strftime('%Y-%m', 'now', '-1 month')"
            : "YEAR(fyh_creacion) = YEAR(CURDATE() - INTERVAL 1 MONTH)
               AND MONTH(fyh_creacion) = MONTH(CURDATE() - INTERVAL 1 MONTH)";
        $returnPeriod = str_replace('fyh_creacion', 'dv.fyh_creacion', $period);
        $sql = "SELECT
                    (SELECT COALESCE(SUM(v.total_pagado), 0) FROM tb_ventas v WHERE $period"
            . ($userId !== null ? " AND v.id_usuario = ?" : '') . ")
                    - (SELECT COALESCE(SUM(dv.monto), 0)
                       FROM tb_devoluciones dv
                       INNER JOIN tb_ventas v2 ON v2.id_venta = dv.id_venta
                       WHERE $returnPeriod"
            . ($userId !== null ? " AND v2.id_usuario = ?" : '') . ") AS total";
        $params = $userId !== null ? [$userId, $userId] : [];
        $rows = $this->query($sql, $params);
        return (float)$rows[0]['total'];
    }

    /**
     * Cantidad y monto de ventas de hoy. Filtra por usuario si se indica $userId.
     *
     * @return array{cantidad: int, monto: float}
     */
    public function todaySummary(?int $userId = null): array
    {
        $isSqlite = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $period = $isSqlite
            ? "DATE(fyh_creacion) = DATE('now')"
            : "DATE(fyh_creacion) = CURDATE()";
        $returnPeriod = str_replace('fyh_creacion', 'dv.fyh_creacion', $period);
        $sql = "SELECT
                    (SELECT COUNT(*) FROM tb_ventas v WHERE $period"
            . ($userId !== null ? " AND v.id_usuario = ?" : '') . ") AS cantidad,
                    (SELECT COALESCE(SUM(v.total_pagado), 0) FROM tb_ventas v WHERE $period"
            . ($userId !== null ? " AND v.id_usuario = ?" : '') . ")
                    - (SELECT COALESCE(SUM(dv.monto), 0)
                       FROM tb_devoluciones dv
                       INNER JOIN tb_ventas v2 ON v2.id_venta = dv.id_venta
                       WHERE $returnPeriod"
            . ($userId !== null ? " AND v2.id_usuario = ?" : '') . ") AS monto";
        $params = $userId !== null ? [$userId, $userId, $userId] : [];
        $rows = $this->query($sql, $params);
        return [
            'cantidad' => (int)$rows[0]['cantidad'],
            'monto' => (float)$rows[0]['monto'],
        ];
    }

    /**
     * Ventas agrupadas por mes — últimos N meses. Filtra por usuario si se indica $userId.
     *
     * @return array Lista de ['mes' => 'YYYY-MM', 'total' => float]
     */
    public function totalsByMonth(int $months = 6, ?int $userId = null): array
    {
        $interval = (int)($months - 1);
        $isSqlite = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $month = $isSqlite ? "strftime('%Y-%m', fyh_creacion)" : "DATE_FORMAT(fyh_creacion, '%Y-%m')";
        $returnMonth = str_replace('fyh_creacion', 'dv.fyh_creacion', $month);
        $start = $isSqlite
            ? "date('now', '-$interval months', 'start of month')"
            : "DATE_FORMAT(CURDATE() - INTERVAL $interval MONTH, '%Y-%m-01')";
        $sql = "SELECT mes, COALESCE(SUM(total), 0) AS total
                FROM (
                    SELECT $month AS mes, SUM(v.total_pagado) AS total
                    FROM tb_ventas v
                    WHERE v.fyh_creacion >= $start"
            . ($userId !== null ? " AND v.id_usuario = ?" : '') . "
                    GROUP BY $month
                    UNION ALL
                    SELECT $returnMonth AS mes, -SUM(dv.monto) AS total
                    FROM tb_devoluciones dv
                    INNER JOIN tb_ventas v2 ON v2.id_venta = dv.id_venta
                    WHERE dv.fyh_creacion >= $start"
            . ($userId !== null ? " AND v2.id_usuario = ?" : '') . "
                    GROUP BY $returnMonth
                ) periods
                GROUP BY mes ORDER BY mes ASC";
        $params = $userId !== null ? [$userId, $userId] : [];
        return $this->query($sql, $params);
    }

    /** Últimas N ventas con nombre del cliente. Filtra por usuario si se indica $userId. */
    public function latest(int $limit = 5, ?int $userId = null): array
    {
        $limit = (int)$limit;
        $sql = "SELECT v.id_venta, v.nro_venta, c.nombre_cliente, v.total_pagado, v.fyh_creacion
                FROM tb_ventas v
                INNER JOIN tb_clientes c ON v.id_cliente = c.id_cliente";
        $params = [];
        if ($userId !== null) {
            $sql .= " WHERE v.id_usuario = ?";
            $params[] = $userId;
        }
        $sql .= " ORDER BY v.id_venta DESC LIMIT $limit";
        return $this->query($sql, $params);
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

    public function isReferenced(int|string $id): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM tb_devoluciones WHERE id_venta = ?'
        );
        $stmt->execute([$id]);

        return (int)$stmt->fetchColumn() > 0;
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

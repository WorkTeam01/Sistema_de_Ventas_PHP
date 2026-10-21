<?php

namespace App\Models;

use App\Core\Model;

class InventoryReport extends Model
{
    /**
     * Resume el saldo inicial, movimientos del período y saldo actual por producto.
     *
     * @param array{desde: string, hasta: string, id_producto?: int} $filters
     */
    public function summary(array $filters): array
    {
        [$movementSql, $movementParams] = $this->movementUnion();
        $movementFilter = 'WHERE DATE(mov.fecha) >= ?';
        $params = [
            $filters['desde'],
            $filters['hasta'],
            $filters['desde'],
            $filters['hasta'],
            $filters['desde'],
        ];
        if (!empty($filters['id_producto'])) {
            $movementFilter .= ' AND mov.id_producto = ?';
            $params[] = (int)$filters['id_producto'];
        }
        $params = array_merge($movementParams, $params);
        $rows = $this->query(
            "SELECT p.id_producto, p.codigo, p.nombre, p.unidad_medida,
                    p.stock AS stock_actual,
                    p.stock - COALESCE(m.neto, 0) AS stock_inicial,
                    COALESCE(m.ingresos, 0) AS ingresos,
                    COALESCE(m.salidas, 0) AS salidas
             FROM tb_almacen p
             LEFT JOIN (
                 SELECT mov.id_producto,
                        SUM(CASE WHEN DATE(mov.fecha) BETWEEN ? AND ? AND mov.cantidad > 0
                            THEN mov.cantidad ELSE 0 END) AS ingresos,
                        SUM(CASE WHEN DATE(mov.fecha) BETWEEN ? AND ? AND mov.cantidad < 0
                            THEN -mov.cantidad ELSE 0 END) AS salidas,
                        SUM(mov.cantidad) AS neto
                 FROM ({$movementSql}) mov
                 {$movementFilter}
                 GROUP BY mov.id_producto
             ) m ON m.id_producto = p.id_producto" .
                (!empty($filters['id_producto']) ? ' WHERE p.id_producto = ?' : '') . "
             ORDER BY p.nombre ASC",
            array_merge($params, !empty($filters['id_producto']) ? [(int)$filters['id_producto']] : [])
        );

        return $rows;
    }

    /**
     * Lista movimientos individuales de stock ocurridos en el período.
     *
     * @param array{desde: string, hasta: string, id_producto?: int} $filters
     */
    public function movements(array $filters): array
    {
        [$movementSql, $params] = $this->movementUnion();
        $sql = "SELECT m.*, m.codigo_producto AS codigo, m.nombre_producto AS producto
                FROM ({$movementSql}) m
                WHERE DATE(m.fecha) BETWEEN ? AND ?";
        $params[] = $filters['desde'];
        $params[] = $filters['hasta'];

        if (!empty($filters['id_producto'])) {
            $sql .= ' AND m.id_producto = ?';
            $params[] = (int)$filters['id_producto'];
        }

        $sql .= ' ORDER BY m.fecha DESC, m.id_producto ASC LIMIT 10000';

        return $this->query($sql, $params);
    }

    /**
     * Une las fuentes existentes que actualizan stock. Unidades de estos movimientos
     * conservan la precisión entera del esquema actual.
     *
     * @return array{0: string, 1: array}
     */
    private function movementUnion(): array
    {
        $sql = "
            SELECT id_producto, codigo_producto, nombre_producto, unidad_medida,
                   fecha, cantidad, tipo, referencia, usuario_nombre AS usuario
            FROM tb_movimientos_inventario";

        return [$sql, []];
    }
}

<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class SaleReturn extends Model
{
    protected string $table = 'tb_devoluciones';
    protected string $primaryKey = 'id_devolucion';

    public function pendingByVenta(int $idVenta): array
    {
        $sale = $this->query(
            'SELECT nro_venta FROM tb_ventas WHERE id_venta = ?',
            [$idVenta]
        );

        if (!$sale) {
            return [];
        }

        return $this->pendingForNumber((int)$sale[0]['nro_venta'], $idVenta);
    }

    public function findWithDetails(int $id): ?array
    {
        $rows = $this->query(
            "SELECT d.*, v.id_venta, v.nro_venta,
                    v.id_usuario AS id_usuario_venta, v.total_pagado,
                    c.nombre_cliente, c.nit_ci_cliente,
                    u.nombres AS usuario_nombre
             FROM tb_devoluciones d
             INNER JOIN tb_ventas v ON v.id_venta = d.id_venta
             LEFT JOIN tb_clientes c ON c.id_cliente = v.id_cliente
             LEFT JOIN tb_usuarios u ON u.id_usuario = d.id_usuario
             WHERE d.id_devolucion = ?",
            [$id]
        );

        if (!$rows) {
            return null;
        }

        $return = $rows[0];
        $return['items'] = $this->query(
            "SELECT i.*, a.nombre AS nombre_producto, a.codigo
             FROM tb_devolucion_items i
             LEFT JOIN tb_almacen a ON a.id_producto = i.id_producto
             WHERE i.id_devolucion = ?
             ORDER BY i.id_detalle ASC",
            [$id]
        );

        return $return;
    }

    public function byVenta(int $idVenta): array
    {
        return $this->query(
            "SELECT d.*, v.nro_venta AS nro_venta_venta
             FROM tb_devoluciones d
             INNER JOIN tb_ventas v ON v.id_venta = d.id_venta
             WHERE d.id_venta = ?
             ORDER BY d.id_devolucion DESC",
            [$idVenta]
        );
    }

    public function allWithDetails(?int $userId = null): array
    {
        $sql = "SELECT d.*, v.nro_venta,
                       v.id_usuario AS id_usuario_venta,
                       c.nombre_cliente, c.nit_ci_cliente,
                       u.nombres AS usuario_nombre
                FROM tb_devoluciones d
                INNER JOIN tb_ventas v ON v.id_venta = d.id_venta
                LEFT JOIN tb_clientes c ON c.id_cliente = v.id_cliente
                LEFT JOIN tb_usuarios u ON u.id_usuario = d.id_usuario";
        $params = [];

        if ($userId !== null) {
            $sql .= ' WHERE v.id_usuario = ?';
            $params[] = $userId;
        }

        $sql .= ' ORDER BY d.id_devolucion DESC';
        return $this->query($sql, $params);
    }

    public function register(int $idVenta, string $motivo, array $quantitiesByProduct): array
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            return ['ok' => false, 'error' => 'validation'];
        }

        $db = $this->db;
        $transactionStarted = false;

        try {
            $db->beginTransaction();
            $transactionStarted = true;

            $lock = $this->forUpdate();
            $stmt = $db->prepare(
                "SELECT nro_venta, id_usuario
                 FROM tb_ventas
                 WHERE id_venta = ?{$lock}"
            );
            $stmt->execute([$idVenta]);
            $sale = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$sale) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }

            $stmt = $db->prepare(
                "SELECT id_producto, cantidad, precio_unitario
                 FROM tb_carrito
                 WHERE nro_venta = ?
                 ORDER BY id_carrito ASC{$lock}"
            );
            $stmt->execute([(int)$sale['nro_venta']]);
            $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $pending = $this->pendingForNumber((int)$sale['nro_venta'], $idVenta);
            $pendingByProduct = [];
            $pricesByProduct = [];
            foreach ($pending as $item) {
                $productId = (int)$item['id_producto'];
                $pendingByProduct[$productId] = (int)$item['pendiente'];
                $pricesByProduct[$productId] = $item['precio_unitario'];
            }

            $requested = [];
            foreach ($quantitiesByProduct as $productId => $quantity) {
                if (
                    !filter_var($productId, FILTER_VALIDATE_INT) || !is_numeric($quantity)
                    || filter_var($quantity, FILTER_VALIDATE_INT) === false
                    || (int)$quantity < 0
                ) {
                    $db->rollBack();
                    return ['ok' => false, 'error' => 'validation'];
                }

                $productId = (int)$productId;
                $quantity = (int)$quantity;
                if (
                    !array_key_exists($productId, $pendingByProduct)
                    || $quantity > $pendingByProduct[$productId]
                ) {
                    $db->rollBack();
                    return ['ok' => false, 'error' => 'conflict'];
                }
                $requested[$productId] = $quantity;
            }

            $lines = [];
            foreach ($requested as $productId => $quantity) {
                if ($quantity < 1) {
                    continue;
                }
                $lines[] = [
                    'id_producto' => $productId,
                    'cantidad' => $quantity,
                    'precio_unitario' => $pricesByProduct[$productId],
                ];
            }

            if (!$lines) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'empty'];
            }

            $user = Auth::user();
            $insert = $db->prepare(
                "INSERT INTO tb_devoluciones
                    (nro_devolucion, id_venta, id_usuario, motivo)
                 VALUES (?, ?, ?, ?)"
            );
            $insert->execute([
                -$idVenta,
                $idVenta,
                $user['id_usuario'] ?? null,
                $motivo,
            ]);
            $id = (int)$db->lastInsertId();

            $insertItem = $db->prepare(
                "INSERT INTO tb_devolucion_items
                    (id_devolucion, id_producto, cantidad, precio_unitario)
                 VALUES (?, ?, ?, ?)"
            );
            $restoreStock = $db->prepare(
                "UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ?"
            );

            foreach ($lines as $line) {
                $insertItem->execute([
                    $id,
                    $line['id_producto'],
                    $line['cantidad'],
                    $line['precio_unitario'],
                ]);
                $restoreStock->execute([$line['cantidad'], $line['id_producto']]);
            }

            $db->prepare(
                "UPDATE tb_devoluciones
                 SET monto = (
                     SELECT COALESCE(SUM(cantidad * precio_unitario), 0)
                     FROM tb_devolucion_items
                     WHERE id_devolucion = ?
                 ),
                 nro_devolucion = id_devolucion
                 WHERE id_devolucion = ?"
            )->execute([$id, $id]);

            $monto = $db->prepare(
                'SELECT monto FROM tb_devoluciones WHERE id_devolucion = ?'
            );
            $monto->execute([$id]);
            $amount = $monto->fetchColumn();

            $this->recordActivity($id, (int)$sale['nro_venta'], $motivo, $amount, $lines);

            $db->commit();
            return [
                'ok' => true,
                'id' => $id,
                'nro' => $id,
                'monto' => (float)$amount,
            ];
        } catch (\Throwable $e) {
            if ($transactionStarted && $db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'error' => 'generic'];
        }
    }

    private function pendingForNumber(int $nroVenta, int $idVenta): array
    {
        return $this->query(
            "SELECT car.id_producto,
                    al.nombre AS nombre_producto,
                    car.cantidad AS vendida,
                    car.precio_unitario,
                    car.cantidad - COALESCE(dev.devuelto, 0) AS pendiente
             FROM tb_carrito car
             LEFT JOIN tb_almacen al ON al.id_producto = car.id_producto
             LEFT JOIN (
                 SELECT det.id_producto, SUM(det.cantidad) AS devuelto
                 FROM tb_devolucion_items det
                 INNER JOIN tb_devoluciones dv
                     ON dv.id_devolucion = det.id_devolucion
                 WHERE dv.id_venta = ?
                 GROUP BY det.id_producto
             ) dev ON dev.id_producto = car.id_producto
             WHERE car.nro_venta = ?
             ORDER BY car.id_carrito ASC",
            [$idVenta, $nroVenta]
        );
    }

    private function forUpdate(): string
    {
        return $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' FOR UPDATE'
            : '';
    }

    private function recordActivity(
        int $id,
        int $nroVenta,
        string $motivo,
        mixed $amount,
        array $lines
    ): void {
        $user = Auth::user();
        $detail = array_map(static fn(array $line): array => [
            'id_producto' => $line['id_producto'],
            'cantidad' => $line['cantidad'],
            'precio_unitario' => $line['precio_unitario'],
        ], $lines);

        $stmt = $this->db->prepare(
            "INSERT INTO tb_activity_log
                (id_usuario, usuario_nombre, accion, entidad, entidad_id,
                 descripcion, datos_nuevos, ip_address)
             VALUES (?, ?, 'create', 'sale_return', ?, ?, ?, ?)"
        );
        $stmt->execute([
            $user['id_usuario'] ?? null,
            trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')),
            $id,
            'Devolución registrada para la venta ' . $nroVenta,
            json_encode([
                'nro_venta' => $nroVenta,
                'motivo' => $motivo,
                'monto_devuelto' => (float)$amount,
                'detalle' => $detail,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}

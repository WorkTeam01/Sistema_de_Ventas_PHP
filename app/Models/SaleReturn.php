<?php

namespace App\Models;

use App\Core\Auth;
use App\Helpers\Money;
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
            "SELECT i.*, i.cantidad * i.precio_unitario AS subtotal,
                    a.nombre AS nombre_producto, a.codigo
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
            "SELECT d.*, v.nro_venta AS nro_venta_venta, u.nombres AS usuario_nombre
             FROM tb_devoluciones d
             INNER JOIN tb_ventas v ON v.id_venta = d.id_venta
             LEFT JOIN tb_usuarios u ON u.id_usuario = d.id_usuario
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

    public static function issueToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function register(
        int $idVenta,
        string $motivo,
        array $quantitiesByProduct,
        ?string $token = null
    ): array {
        $motivo = trim($motivo);
        if ($motivo === '' || ($token !== null && !preg_match('/^[0-9a-f]{32}$/', $token))) {
            return ['ok' => false, 'error' => 'validation'];
        }

        // Un reenvío del mismo formulario devuelve la devolución original sin tocar stock
        if ($token !== null && ($original = $this->findByToken($idVenta, $token)) !== null) {
            return $this->replayed($original);
        }

        $db = $this->db;
        $transactionStarted = false;

        try {
            $db->beginTransaction();
            $transactionStarted = true;

            $lock = $this->forUpdate();
            $stmt = $db->prepare(
                "SELECT nro_venta, id_usuario, total_pagado
                 FROM tb_ventas
                 WHERE id_venta = ?{$lock}"
            );
            $stmt->execute([$idVenta]);
            $sale = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$sale) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }

            // Con el bloqueo de la venta ya tomado, un reenvío concurrente ve aquí la devolución
            // que acaba de confirmar el ganador, aunque ya no quede nada pendiente
            if ($token !== null && ($original = $this->findByToken($idVenta, $token)) !== null) {
                $db->rollBack();
                return $this->replayed($original);
            }

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

            // Una devolución nunca reintegra más de lo cobrado: el exceso (ventas con
            // ajuste de precios) se descarta del monto, pero el stock se repone completo
            $calculatedCents = 0;
            foreach ($lines as $line) {
                $calculatedCents += $line['cantidad'] * (Money::toCents($line['precio_unitario']) ?? 0);
            }
            $returnedStmt = $db->prepare(
                'SELECT COALESCE(SUM(monto), 0) FROM tb_devoluciones WHERE id_venta = ?'
            );
            $returnedStmt->execute([$idVenta]);
            $remainingCents = (Money::toCents($sale['total_pagado']) ?? 0)
                - (Money::toCents($returnedStmt->fetchColumn()) ?? 0);
            $amountCents = max(0, min($calculatedCents, $remainingCents));

            $user = Auth::user();
            $insert = $db->prepare(
                "INSERT INTO tb_devoluciones
                    (nro_devolucion, id_venta, id_usuario, motivo, token_formulario)
                 VALUES (?, ?, ?, ?, ?)"
            );
            try {
                $insert->execute([
                    -$idVenta,
                    $idVenta,
                    $user['id_usuario'] ?? null,
                    $motivo,
                    $token,
                ]);
            } catch (\PDOException $e) {
                $db->rollBack();
                if (!$this->isDuplicateKey($e)) {
                    throw $e;
                }

                // Choque de clave única: o es el reenvío concurrente del mismo formulario
                // (se devuelve el ganador) o el número temporal ya lo tomó otra devolución
                $original = $token !== null ? $this->findByToken($idVenta, $token) : null;
                return $original !== null
                    ? $this->replayed($original)
                    : ['ok' => false, 'error' => 'conflict'];
            }
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
                (new InventoryMovement())->record(
                    (int)$line['id_producto'],
                    (int)$line['cantidad'],
                    'Devolución',
                    'Devolución Nro ' . $id,
                    isset($user['id_usuario']) ? (int)$user['id_usuario'] : null,
                    (string)($user['nombres'] ?? '')
                );
            }

            $db->prepare(
                "UPDATE tb_devoluciones
                 SET monto = ?, nro_devolucion = id_devolucion
                 WHERE id_devolucion = ?"
            )->execute([Money::fromCents($amountCents), $id]);
            $amount = Money::fromCents($amountCents);

            $this->recordActivity($id, (int)$sale['nro_venta'], $motivo, $amount, $lines);

            $db->commit();
            return [
                'ok' => true,
                'id' => $id,
                'nro' => $id,
                'monto' => (float)$amount,
                'replayed' => false,
            ];
        } catch (\Throwable $e) {
            if ($transactionStarted && $db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'error' => 'generic'];
        }
    }

    private function isDuplicateKey(\PDOException $e): bool
    {
        // MySQL/MariaDB: 1062; SQLite no expone un código propio para UNIQUE, solo el texto
        return ($e->errorInfo[1] ?? null) === 1062
            || ($e->getCode() === '23000' && str_contains($e->getMessage(), 'UNIQUE constraint failed'));
    }

    protected function findByToken(int $idVenta, string $token): ?array
    {
        $rows = $this->query(
            'SELECT id_devolucion, monto FROM tb_devoluciones
             WHERE token_formulario = ? AND id_venta = ?',
            [$token, $idVenta]
        );

        return $rows[0] ?? null;
    }

    private function replayed(array $original): array
    {
        return [
            'ok' => true,
            'id' => (int)$original['id_devolucion'],
            'nro' => (int)$original['id_devolucion'],
            'monto' => (float)$original['monto'],
            'replayed' => true,
        ];
    }

    private function pendingForNumber(int $nroVenta, int $idVenta): array
    {
        return $this->query(
            "SELECT car.id_producto,
                    al.nombre AS nombre_producto,
                    car.vendida,
                    car.precio_unitario,
                    car.vendida - COALESCE(dev.devuelto, 0) AS pendiente
             FROM (
                 SELECT id_producto,
                        SUM(cantidad) AS vendida,
                        ROUND(SUM(cantidad * precio_unitario) / SUM(cantidad), 2) AS precio_unitario,
                        MIN(id_carrito) AS primera_linea
                 FROM tb_carrito
                 WHERE nro_venta = ?
                 GROUP BY id_producto
             ) car
             LEFT JOIN tb_almacen al ON al.id_producto = car.id_producto
             LEFT JOIN (
                 SELECT det.id_producto, SUM(det.cantidad) AS devuelto
                 FROM tb_devolucion_items det
                 INNER JOIN tb_devoluciones dv
                     ON dv.id_devolucion = det.id_devolucion
                 WHERE dv.id_venta = ?
                 GROUP BY det.id_producto
             ) dev ON dev.id_producto = car.id_producto
             ORDER BY car.primera_linea ASC",
            [$nroVenta, $idVenta]
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
            $user['nombres'] ?? '',
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

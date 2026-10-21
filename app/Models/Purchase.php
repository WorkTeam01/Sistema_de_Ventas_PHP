<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;
use App\Helpers\MonthSeries;

/**
 * Modelo para la tabla tb_compras.
 *
 * Hereda find(), count() de Model.
 * Las operaciones de escritura son transaccionales porque afectan tb_almacen (stock).
 */
class Purchase extends Model
{
    protected string $table = 'tb_compras';
    public const MAX_PRICE = 9999999.99;
    public const MAX_QUANTITY = 1000000;

    protected string $primaryKey = 'id_compra';

    /**
     * Retorna todas las compras con datos de producto, proveedor y usuario.
     * Filtra por usuario si se indica $userId.
     *
     * @return array Lista de compras con datos relacionados.
     */
    public function allWithDetails(?int $userId = null): array
    {
        $sql = "SELECT co.*, al.codigo, al.nombre AS nombre_producto, al.unidad_medida, al.imagen,
                       pro.nombre_proveedor, us.email AS email_usuario
                FROM tb_compras co
                INNER JOIN tb_almacen al ON co.id_producto = al.id_producto
                INNER JOIN tb_proveedores pro ON co.id_proveedor = pro.id_proveedor
                INNER JOIN tb_usuarios us ON co.id_usuario = us.id_usuario";
        $params = [];
        if ($userId !== null) {
            $sql .= " WHERE co.id_usuario = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY co.id_compra DESC";
        return $this->query($sql, $params);
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
            "SELECT co.*, al.codigo, al.nombre AS nombre_producto, al.unidad_medida,
                    al.imagen, al.stock, al.stock_minimo, al.stock_maximo,
                    al.precio_compra AS precio_compra_producto, al.precio_venta,
                    al.descripcion AS descripcion_producto,
                    pro.nombre_proveedor, pro.empresa,
                    pro.celular, pro.telefono, pro.email AS email_proveedor,
                    pro.direccion, us.email AS email_usuario,
                    us.nombres AS nombre_usuario,
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
        $rows = $this->query("SELECT COALESCE(MAX(nro_compra), 0) + 1 AS next FROM tb_compras");
        return (int)($rows[0]['next'] ?? 1);
    }

    /**
     * Inserta una compra y actualiza el stock del producto en una transacción.
     *
     * @param array $data Datos de la compra.
     * @return int|false ID de la compra insertada, o false si hubo error.
     */
    public function storeWithStock(array $data): int|false
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
            $idCompra = (int)$db->lastInsertId();
            $db->prepare(
                "UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ?"
            )->execute([$data['cantidad'], $data['id_producto']]);
            (new InventoryMovement())->record(
                (int)$data['id_producto'],
                (int)$data['cantidad'],
                'Compra',
                (string)$data['comprobante'],
                (int)$data['id_usuario'],
                $this->userName((int)$data['id_usuario']),
                (string)$data['fecha_compra'] . ' 00:00:00'
            );
            $db->commit();
            return $idCompra;
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
     * El `id_usuario` original de la compra no se modifica. Si algún producto quedaría
     * con stock negativo se revierte todo.
     *
     * @param array $data Datos nuevos de la compra (incluye id_compra).
     * Producto y cantidad previos se releen con la fila bloqueada: dos ediciones simultáneas
     * parten del mismo valor y el delta de stock se calcularía dos veces.
     *
     * @param int $oldProductId ID del producto antes de la edición (referencial; manda la fila bloqueada).
     * @param int $oldCantidad Cantidad antes de la edición (referencial; manda la fila bloqueada).
     * @return array{ok: bool, error: ?string, available: ?int} error: 'stock_insufficient' | 'not_found' | 'generic'.
     */
    public function updateWithStock(array $data, int $oldProductId, int $oldCantidad): array
    {
        $db = $this->db;
        try {
            $db->beginTransaction();

            $current = $this->lockPurchase((int)$data['id_compra']);
            if ($current === null) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'not_found', 'available' => null];
            }
            $oldProductId = (int)$current['id_producto'];
            $oldCantidad = (int)$current['cantidad'];

            $db->prepare(
                "UPDATE tb_compras
                 SET id_producto=?, nro_compra=?, fecha_compra=?, id_proveedor=?,
                     comprobante=?, precio_compra=?, cantidad=?
                 WHERE id_compra=?"
            )->execute([
                $data['id_producto'],
                $data['nro_compra'],
                $data['fecha_compra'],
                $data['id_proveedor'],
                $data['comprobante'],
                $data['precio_compra'],
                $data['cantidad'],
                $data['id_compra'],
            ]);

            $newProductId = (int)$data['id_producto'];
            $newQuantity = (int)$data['cantidad'];
            $deltas = ($newProductId === $oldProductId)
                ? [[$oldProductId, $newQuantity - $oldCantidad]]
                : [[$oldProductId, -$oldCantidad], [$newProductId, $newQuantity]];

            foreach ($deltas as [$productId, $delta]) {
                $failure = $this->applyStockDelta((int)$productId, (int)$delta);
                if ($failure !== null) {
                    $db->rollBack();
                    return $failure;
                }
                if ($delta !== 0) {
                    $this->recordCorrectionMovement(
                        (int)$productId,
                        (int)$delta,
                        (int)$current['nro_compra'],
                        $current
                    );
                }
            }

            $db->commit();
            return ['ok' => true, 'error' => null, 'available' => null];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'error' => 'generic', 'available' => null];
        }
    }

    /**
     * Lee id_producto y cantidad de la compra bloqueando la fila hasta el fin de la transacción
     * (SQLite no admite FOR UPDATE: su transacción ya serializa).
     *
     * @return array{id_producto: mixed, cantidad: mixed}|null
     */
    private function lockPurchase(int $id): ?array
    {
        $lock = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
        $stmt = $this->db->prepare(
            "SELECT id_producto, cantidad, nro_compra, id_usuario FROM tb_compras WHERE id_compra = ?" . $lock
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Suma o resta stock con un UPDATE relativo que no deja el stock bajo 0.
     *
     * @return array{ok: false, error: string, available: ?int}|null null si se aplicó.
     */
    private function applyStockDelta(int $productId, int $delta): ?array
    {
        if ($delta === 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ? AND stock + ? >= 0"
        );
        $stmt->execute([$delta, $productId, $delta]);

        if ($stmt->rowCount() > 0) {
            return null;
        }

        $rows = $this->query("SELECT stock FROM tb_almacen WHERE id_producto = ?", [$productId]);

        return empty($rows)
            ? ['ok' => false, 'error' => 'generic', 'available' => null]
            : ['ok' => false, 'error' => 'stock_insufficient', 'available' => (int)$rows[0]['stock']];
    }

    /** Suma de compras del mes actual (precio_compra * cantidad por fila). Filtra por usuario si se indica $userId. */
    public function totalCurrentMonth(?int $userId = null): float
    {
        $sql = "SELECT COALESCE(SUM(precio_compra * cantidad), 0) AS total
                FROM tb_compras
                WHERE YEAR(fecha_compra) = YEAR(CURDATE())
                  AND MONTH(fecha_compra) = MONTH(CURDATE())";
        $params = [];
        if ($userId !== null) {
            $sql .= " AND id_usuario = ?";
            $params[] = $userId;
        }
        $rows = $this->query($sql, $params);
        return (float)$rows[0]['total'];
    }

    /** Suma de compras del mes anterior. Filtra por usuario si se indica $userId. */
    public function totalPreviousMonth(?int $userId = null): float
    {
        $sql = "SELECT COALESCE(SUM(precio_compra * cantidad), 0) AS total
                FROM tb_compras
                WHERE YEAR(fecha_compra) = YEAR(CURDATE() - INTERVAL 1 MONTH)
                  AND MONTH(fecha_compra) = MONTH(CURDATE() - INTERVAL 1 MONTH)";
        $params = [];
        if ($userId !== null) {
            $sql .= " AND id_usuario = ?";
            $params[] = $userId;
        }
        $rows = $this->query($sql, $params);
        return (float)$rows[0]['total'];
    }

    /**
     * Compras agrupadas por mes — últimos N meses, desde el primer día del mes más antiguo de
     * MonthSeries (la misma serie que rotula el gráfico). Filtra por usuario si se indica $userId.
     *
     * @param \DateTimeImmutable|null $base Fecha de referencia; hoy si es null.
     * @return array Lista de ['mes' => 'YYYY-MM', 'total' => float]
     */
    public function totalsByMonth(int $months, ?int $userId = null, ?\DateTimeImmutable $base = null): array
    {
        $start = MonthSeries::lastMonths(max(1, $months), $base)[0] . '-01';
        $sql = "SELECT DATE_FORMAT(fecha_compra, '%Y-%m') AS mes,
                       COALESCE(SUM(precio_compra * cantidad), 0) AS total
                FROM tb_compras
                WHERE fecha_compra >= ?";
        $params = [$start];
        if ($userId !== null) {
            $sql .= " AND id_usuario = ?";
            $params[] = $userId;
        }
        $sql .= " GROUP BY DATE_FORMAT(fecha_compra, '%Y-%m') ORDER BY mes ASC";
        return $this->query($sql, $params);
    }

    /**
     * Elimina una compra y revierte el stock del producto en una transacción.
     *
     * Borrar una compra que ya no existe es un no-op exitoso (`already`): no toca el stock,
     * así un doble envío no lo descuenta dos veces.
     *
     * @param int $id ID de la compra.
     * @return array{ok: bool, error: ?string, available: ?int, already?: bool} error: 'stock_insufficient' | 'generic'.
     */
    public function destroyWithStock(int $id): array
    {
        $db = $this->db;
        try {
            $db->beginTransaction();

            $current = $this->lockPurchase($id);
            if ($current === null) {
                $db->rollBack();
                return ['ok' => true, 'error' => null, 'available' => null, 'already' => true];
            }

            $db->prepare("DELETE FROM tb_compras WHERE id_compra = ?")->execute([$id]);

            $failure = $this->applyStockDelta((int)$current['id_producto'], -(int)$current['cantidad']);
            if ($failure !== null) {
                $db->rollBack();
                return $failure;
            }
            $this->recordCorrectionMovement(
                (int)$current['id_producto'],
                -(int)$current['cantidad'],
                (int)$current['nro_compra'],
                $current
            );

            $db->commit();
            return ['ok' => true, 'error' => null, 'available' => null];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'error' => 'generic', 'available' => null];
        }
    }

    /** Indica si ya existe una compra con ese número, ignorando la que se está editando. */
    public function nroCompraExists(int $nro, ?int $excludeId = null): bool
    {
        $rows = $this->query(
            "SELECT 1 FROM tb_compras WHERE nro_compra = ? AND id_compra <> ? LIMIT 1",
            [$nro, $excludeId ?? 0]
        );

        return !empty($rows);
    }

    /**
     * Valida los datos de una compra.
     * Realiza validaciones exhaustivas en el servidor (verdad absoluta).
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
        } elseif ($this->nroCompraExists((int)$data['nro_compra'], isset($data['id_compra']) ? (int)$data['id_compra'] : null)) {
            $errors['nro_compra'] = 'El número de compra ya existe.';
        }
        if (empty($data['fecha_compra'])) {
            $errors['fecha_compra'] = 'Fecha requerida.';
        } else {
            $date = \DateTime::createFromFormat('Y-m-d', (string)$data['fecha_compra']);
            if (!$date || $date->format('Y-m-d') !== $data['fecha_compra']) {
                $errors['fecha_compra'] = 'Fecha inválida.';
            } elseif ($data['fecha_compra'] > date('Y-m-d')) {
                $errors['fecha_compra'] = 'La fecha de compra no puede ser futura.';
            }
        }
        if (empty($data['comprobante'])) {
            $errors['comprobante'] = 'Comprobante requerido.';
        } elseif (strlen(trim($data['comprobante'])) < 3) {
            $errors['comprobante'] = 'Comprobante debe tener al menos 3 caracteres.';
        }
        if (!is_numeric($data['precio_compra'] ?? '')) {
            $errors['precio_compra'] = 'Precio debe ser numérico.';
        } elseif ((float)$data['precio_compra'] <= 0) {
            $errors['precio_compra'] = 'Precio debe ser mayor a 0.';
        } elseif (round((float)$data['precio_compra'], 2) > self::MAX_PRICE) {
            $errors['precio_compra'] = 'Precio no puede superar 9.999.999,99.';
        }
        if (!is_numeric($data['cantidad'] ?? '')) {
            $errors['cantidad'] = 'Cantidad debe ser numérica.';
        } elseif (filter_var($data['cantidad'], FILTER_VALIDATE_INT) === false) {
            $errors['cantidad'] = 'Cantidad debe ser un número entero.';
        } elseif ((int)$data['cantidad'] < 1) {
            $errors['cantidad'] = 'Cantidad debe ser mayor a cero.';
        } elseif ((int)$data['cantidad'] > self::MAX_QUANTITY) {
            $errors['cantidad'] = 'Cantidad no puede superar 1.000.000.';
        }

        return empty($errors) ? true : $errors;
    }

    private function recordCorrectionMovement(int $productId, int $quantity, int $purchaseNumber, array $purchase): void
    {
        $user = Auth::user();
        $actorId = isset($user['id_usuario']) ? (int)$user['id_usuario'] : (int)($purchase['id_usuario'] ?? 0);
        $userId = $actorId > 0 ? $actorId : null;
        $userName = (string)($user['nombres'] ?? $this->userName($userId));

        (new InventoryMovement())->record(
            $productId,
            $quantity,
            'Corrección de compra',
            'Compra Nro ' . $purchaseNumber,
            $userId,
            $userName
        );
    }

    private function userName(?int $userId): string
    {
        if ($userId === null) {
            return 'Usuario eliminado';
        }

        $user = Auth::user();
        if ((int)($user['id_usuario'] ?? 0) === $userId && !empty($user['nombres'])) {
            return (string)$user['nombres'];
        }

        $rows = $this->query('SELECT nombres FROM tb_usuarios WHERE id_usuario = ?', [$userId]);

        return (string)($rows[0]['nombres'] ?? 'Usuario eliminado');
    }
}

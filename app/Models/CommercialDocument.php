<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\Money;

class CommercialDocument extends Model
{
    protected string $table = 'tb_documentos_comerciales';
    protected string $primaryKey = 'id_documento';

    public const TYPES = [
        'cotizacion' => 'Cotización',
        'orden_compra' => 'Orden de compra',
    ];

    private const STATUSES = [
        'cotizacion' => ['borrador', 'emitida', 'aceptada', 'rechazada', 'vencida'],
        'orden_compra' => ['borrador', 'emitida', 'cancelada'],
    ];

    public function allWithDetails(string $type, ?int $userId = null): array
    {
        $sql = "SELECT d.*, c.nombre_cliente, s.nombre_proveedor, u.nombres AS nombre_usuario,
                       (SELECT COUNT(*) FROM tb_documento_comercial_items i
                        WHERE i.id_documento = d.id_documento) AS cantidad_items
                FROM tb_documentos_comerciales d
                LEFT JOIN tb_clientes c ON c.id_cliente = d.id_cliente
                LEFT JOIN tb_proveedores s ON s.id_proveedor = d.id_proveedor
                LEFT JOIN tb_usuarios u ON u.id_usuario = d.id_usuario
                WHERE d.tipo = ?";
        $params = [$type];

        if ($userId !== null) {
            $sql .= ' AND d.id_usuario = ?';
            $params[] = $userId;
        }

        return $this->query($sql . ' ORDER BY d.id_documento DESC', $params);
    }

    public function findWithDetails(int $id): ?array
    {
        $rows = $this->query(
            "SELECT d.*, c.nombre_cliente, c.nit_ci_cliente, s.nombre_proveedor,
                    s.empresa, u.nombres AS nombre_usuario
             FROM tb_documentos_comerciales d
             LEFT JOIN tb_clientes c ON c.id_cliente = d.id_cliente
             LEFT JOIN tb_proveedores s ON s.id_proveedor = d.id_proveedor
             LEFT JOIN tb_usuarios u ON u.id_usuario = d.id_usuario
             WHERE d.id_documento = ?",
            [$id]
        );
        if (!$rows) {
            return null;
        }

        $document = $rows[0];
        $document['items'] = $this->query(
            'SELECT * FROM tb_documento_comercial_items WHERE id_documento = ? ORDER BY id_item',
            [$id]
        );

        return $document;
    }

    /**
     * Persiste el encabezado y sus líneas sin modificar existencias.
     *
     * @return array{ok: bool, id: ?int, error: ?string}
     */
    public function createDocument(string $type, array $data, int $userId): array
    {
        if (!isset(self::TYPES[$type])) {
            return ['ok' => false, 'id' => null, 'error' => 'invalid_type'];
        }
        if ($this->validateData($type, $data) !== true) {
            return ['ok' => false, 'id' => null, 'error' => 'invalid_items'];
        }

        $db = $this->db;
        try {
            $db->beginTransaction();
            $db->prepare(
                'INSERT INTO tb_documentos_comerciales
                    (tipo, estado, fecha_documento, fecha_vencimiento, id_cliente, id_proveedor,
                     id_usuario, observaciones, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $type,
                $data['estado'],
                $data['fecha_documento'],
                $data['fecha_vencimiento'] !== '' ? $data['fecha_vencimiento'] : null,
                $type === 'cotizacion' ? $data['id_contacto'] : null,
                $type === 'orden_compra' ? $data['id_contacto'] : null,
                $userId,
                $data['observaciones'] !== '' ? $data['observaciones'] : null,
                0,
            ]);
            $id = (int)$db->lastInsertId();
            $total = $this->insertLines($id, $type, $data['items'] ?? []);
            if ($total === null) {
                $db->rollBack();
                return ['ok' => false, 'id' => null, 'error' => 'invalid_items'];
            }
            $db->prepare('UPDATE tb_documentos_comerciales SET total = ? WHERE id_documento = ?')
                ->execute([Money::fromCents($total), $id]);
            $this->writeHistory($id, 'crear', null, $this->findWithDetails($id), $userId, (string)$data['usuario_nombre']);
            $db->commit();

            return ['ok' => true, 'id' => $id, 'error' => null];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[CommercialDocument] fallo al crear: ' . $e->getMessage());
            return ['ok' => false, 'id' => null, 'error' => 'generic'];
        }
    }

    /**
     * Actualiza un documento y sus líneas dentro de una transacción.
     *
     * @return array{ok: bool, error: ?string}
     */
    public function updateDocument(int $id, string $type, array $data, int $userId): array
    {
        if (!isset(self::TYPES[$type])) {
            return ['ok' => false, 'error' => 'invalid_type'];
        }
        if ($this->validateData($type, $data) !== true) {
            return ['ok' => false, 'error' => 'invalid_items'];
        }

        $db = $this->db;
        try {
            $db->beginTransaction();
            $lock = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
            $stmt = $db->prepare('SELECT tipo FROM tb_documentos_comerciales WHERE id_documento = ?' . $lock);
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() !== $type) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }

            $before = $this->findWithDetails($id);
            $total = $this->insertLines($id, $type, $data['items'] ?? []);
            if ($total === null) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'invalid_items'];
            }

            $db->prepare(
                'UPDATE tb_documentos_comerciales
                 SET estado = ?, fecha_documento = ?, fecha_vencimiento = ?, id_cliente = ?,
                     id_proveedor = ?, observaciones = ?, total = ?
                 WHERE id_documento = ?'
            )->execute([
                $data['estado'],
                $data['fecha_documento'],
                $data['fecha_vencimiento'] !== '' ? $data['fecha_vencimiento'] : null,
                $type === 'cotizacion' ? $data['id_contacto'] : null,
                $type === 'orden_compra' ? $data['id_contacto'] : null,
                $data['observaciones'] !== '' ? $data['observaciones'] : null,
                Money::fromCents($total),
                $id,
            ]);

            $this->writeHistory($id, 'corregir', $before, $this->findWithDetails($id), $userId, (string)$data['usuario_nombre']);
            $db->commit();
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[CommercialDocument] fallo al actualizar: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'generic'];
        }
    }

    /**
     * Reemplaza las líneas y devuelve el total en céntimos. ID 0 guarda líneas
     * pendientes dentro de la misma transacción de creación.
     */
    private function insertLines(int $documentId, string $type, array $items): ?int
    {
        if ($items === [] || count($items['id_producto'] ?? []) < 1 || count($items['id_producto']) > 100) {
            return null;
        }
        if ($documentId > 0) {
            $this->db->prepare('DELETE FROM tb_documento_comercial_items WHERE id_documento = ?')
                ->execute([$documentId]);
        }

        $productPrice = $type === 'cotizacion' ? 'precio_venta' : 'precio_compra';
        $totalCents = 0;
        $insert = $this->db->prepare(
            'INSERT INTO tb_documento_comercial_items
                (id_documento, id_producto, codigo_producto, nombre_producto, unidad_medida,
                 cantidad, precio_unitario, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $productStmt = $this->db->prepare(
            "SELECT id_producto, codigo, nombre, unidad_medida, {$productPrice} AS precio
             FROM tb_almacen WHERE id_producto = ?"
        );

        foreach ($items['id_producto'] as $index => $rawProductId) {
            $productId = filter_var($rawProductId, FILTER_VALIDATE_INT);
            $quantity = filter_var($items['cantidad'][$index] ?? null, FILTER_VALIDATE_INT);
            $priceCents = Money::toCents($items['precio_unitario'][$index] ?? '');
            if ((!is_string($rawProductId) && !is_int($rawProductId))
                || (!is_string($items['cantidad'][$index] ?? null) && !is_int($items['cantidad'][$index] ?? null))
                || (!is_string($items['precio_unitario'][$index] ?? null)
                    && !is_int($items['precio_unitario'][$index] ?? null)
                    && !is_float($items['precio_unitario'][$index] ?? null))
                || $productId === false || $productId <= 0 || $quantity === false || $quantity <= 0
                || $quantity > 1000000 || $priceCents === null || $priceCents < 0) {
                return null;
            }

            $productStmt->execute([$productId]);
            $product = $productStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$product) {
                return null;
            }

            if ($priceCents > intdiv(999999999999, $quantity)) {
                return null;
            }
            $lineCents = $quantity * $priceCents;
            if ($totalCents > 999999999999 - $lineCents) {
                return null;
            }
            $totalCents += $lineCents;
            $insert->execute([
                $documentId,
                $productId,
                $product['codigo'],
                $product['nombre'],
                $product['unidad_medida'],
                $quantity,
                Money::fromCents($priceCents),
                Money::fromCents($lineCents),
            ]);
        }

        return $totalCents;
    }

    public function validateData(string $type, array $data): true|string
    {
        if (!isset(self::TYPES[$type])) {
            return 'Tipo de documento inválido.';
        }
        if (!is_string($data['estado'] ?? null) || !in_array($data['estado'], self::STATUSES[$type], true)) {
            return 'Seleccione un estado válido.';
        }
        if (!is_int($data['id_contacto'] ?? null) || $data['id_contacto'] < 1) {
            return $type === 'cotizacion' ? 'Seleccione un cliente.' : 'Seleccione un proveedor.';
        }
        foreach (['fecha_documento', 'fecha_vencimiento'] as $field) {
            if (!is_string($data[$field] ?? '')) {
                return 'Ingrese fechas válidas.';
            }
            if (($data[$field] ?? '') === '') {
                if ($field === 'fecha_vencimiento' && $type === 'orden_compra') {
                    continue;
                }
                if ($field === 'fecha_vencimiento' && $type === 'cotizacion') {
                    continue;
                }
                return 'Ingrese la fecha del documento.';
            }
            if ($data[$field] !== '') {
                $date = \DateTime::createFromFormat('!Y-m-d', (string)$data[$field]);
                if (!$date || $date->format('Y-m-d') !== $data[$field]) {
                    return 'Ingrese fechas válidas.';
                }
            }
        }
        if (!is_string($data['observaciones'] ?? null) || mb_strlen($data['observaciones']) > 1000) {
            return 'Las observaciones no pueden superar 1.000 caracteres.';
        }
        $items = $data['items'] ?? [];
        if (!is_array($items) || !isset($items['id_producto'], $items['cantidad'], $items['precio_unitario'])
            || !is_array($items['id_producto']) || !is_array($items['cantidad']) || !is_array($items['precio_unitario'])
            || count($items['id_producto']) !== count($items['cantidad'])
            || count($items['id_producto']) !== count($items['precio_unitario'])
            || !array_is_list($items['id_producto']) || !array_is_list($items['cantidad'])
            || !array_is_list($items['precio_unitario'])
            || count($items['id_producto']) < 1 || count($items['id_producto']) > 100) {
            return 'Agregue entre 1 y 100 productos al documento.';
        }
        foreach ($items['id_producto'] as $index => $productId) {
            $quantity = $items['cantidad'][$index];
            $price = $items['precio_unitario'][$index];
            if ((!is_string($productId) && !is_int($productId))
                || (!is_string($quantity) && !is_int($quantity))
                || (!is_string($price) && !is_int($price) && !is_float($price))
                || filter_var($productId, FILTER_VALIDATE_INT) === false
                || filter_var($quantity, FILTER_VALIDATE_INT) === false
                || (int)$productId < 1 || (int)$quantity < 1 || (int)$quantity > 1000000
                || Money::toCents($price) === null) {
                return 'Revise los productos, cantidades y precios ingresados.';
            }
        }

        return true;
    }

    public function historyFor(int $id): array
    {
        return $this->query(
            'SELECT id_historial, id_usuario, usuario_nombre, accion, datos_anteriores,
                    datos_nuevos, fyh_creacion
             FROM tb_documentos_comerciales_historial
             WHERE id_documento = ?
             ORDER BY id_historial DESC',
            [$id]
        );
    }

    private function writeHistory(
        int $id,
        string $action,
        ?array $before,
        ?array $after,
        int $userId,
        string $userName
    ): void {
        $this->db->prepare(
            'INSERT INTO tb_documentos_comerciales_historial
                (id_documento, id_usuario, usuario_nombre, accion, datos_anteriores, datos_nuevos)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $id,
            $userId,
            $userName,
            $action,
            $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            json_encode($after ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }
}

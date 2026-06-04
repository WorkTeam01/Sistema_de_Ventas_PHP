<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

/**
 * Modelo para ajustes manuales de stock (tabla tb_ajustes_stock).
 */
class StockAdjustment extends Model
{
    protected string $table = 'tb_ajustes_stock';
    protected string $primaryKey = 'id_ajuste';

    /**
     * Registra un ajuste de stock dentro de una transacción atómica.
     *
     * @param int    $idProducto ID del producto a ajustar.
     * @param string $tipo       'entrada' o 'salida'.
     * @param int    $cantidad   Unidades a ajustar (positivo).
     * @param string $motivo     Descripción del motivo del ajuste.
     * @return array{ok: bool, stock_posterior?: int, error?: string}
     */
    public function register(int $idProducto, string $tipo, int $cantidad, string $motivo): array
    {
        try {
            $this->db->beginTransaction();

            // 1. Leer stock actual
            $rows = $this->query(
                "SELECT stock FROM tb_almacen WHERE id_producto = ?",
                [$idProducto]
            );

            if (empty($rows)) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Producto no encontrado'];
            }

            $stock_actual = (int)$rows[0]['stock'];

            // 2. Verificar stock suficiente para salida
            if ($tipo === 'salida' && $stock_actual < $cantidad) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'Stock insuficiente para realizar la salida'];
            }

            // 3. Calcular stock posterior
            $stock_posterior = ($tipo === 'entrada')
                ? $stock_actual + $cantidad
                : $stock_actual - $cantidad;

            // 4. Actualizar stock en tb_almacen
            $stmt = $this->db->prepare(
                "UPDATE tb_almacen SET stock = ? WHERE id_producto = ?"
            );
            $stmt->execute([$stock_posterior, $idProducto]);

            // 5. Obtener datos del usuario autenticado
            $user = Auth::user();
            $id_usuario     = $user['id_usuario'] ?? null;
            $usuario_nombre = trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? ''));

            // 6. Insertar registro de ajuste
            $this->create([
                'id_producto'    => $idProducto,
                'tipo'           => $tipo,
                'cantidad'       => $cantidad,
                'stock_anterior' => $stock_actual,
                'stock_posterior' => $stock_posterior,
                'motivo'         => $motivo,
                'id_usuario'     => $id_usuario,
                'usuario_nombre' => $usuario_nombre,
            ]);

            // 7. Registrar en el log de auditoría (nunca lanza excepción)
            ActivityLog::record(
                'stock_adjustment',
                'product',
                $idProducto,
                "Ajuste de stock: {$tipo} de {$cantidad} unidades",
                ['stock' => $stock_actual],
                ['stock' => $stock_posterior]
            );

            $this->db->commit();

            return ['ok' => true, 'stock_posterior' => $stock_posterior];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return ['ok' => false, 'error' => 'Error interno al registrar el ajuste'];
        }
    }

    /**
     * Retorna el historial de ajustes con datos del producto, aplicando filtros opcionales.
     *
     * @param array $filters Claves opcionales: desde, hasta, tipo, id_producto.
     * @return array Lista de ajustes ordenada por id_ajuste DESC.
     */
    public function history(array $filters = [], int $limit = 0): array
    {
        $where  = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['desde'])) {
            $where   .= ' AND aj.fyh_creacion >= ?';
            $params[] = $filters['desde'] . ' 00:00:00';
        }

        if (!empty($filters['hasta'])) {
            $where   .= ' AND aj.fyh_creacion <= ?';
            $params[] = $filters['hasta'] . ' 23:59:59';
        }

        if (!empty($filters['tipo']) && in_array($filters['tipo'], ['entrada', 'salida'], true)) {
            $where   .= ' AND aj.tipo = ?';
            $params[] = $filters['tipo'];
        }

        if (!empty($filters['id_producto'])) {
            $where   .= ' AND aj.id_producto = ?';
            $params[] = (int)$filters['id_producto'];
        }

        $limitClause = $limit > 0 ? "LIMIT {$limit}" : '';

        return $this->query(
            "SELECT aj.*, al.nombre AS producto_nombre, al.codigo AS producto_codigo
             FROM tb_ajustes_stock aj
             INNER JOIN tb_almacen al ON aj.id_producto = al.id_producto
             {$where}
             ORDER BY aj.id_ajuste DESC {$limitClause}",
            $params
        );
    }
}

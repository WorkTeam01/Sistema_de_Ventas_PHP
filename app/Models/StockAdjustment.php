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
     * Registra un ajuste de stock en su propia transacción atómica.
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

            $result = $this->registerInTransaction($idProducto, $tipo, $cantidad, $motivo);

            if (!$result['ok']) {
                $this->db->rollBack();
                return $result;
            }

            $this->db->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['ok' => false, 'error' => 'Error interno al registrar el ajuste'];
        }
    }

    /**
     * Aplica el ajuste dentro de una transacción ya abierta por el llamador
     * (no abre ni cierra ninguna). Si devuelve `ok=false` el llamador debe
     * hacer rollback; las excepciones de PDO se propagan.
     *
     * El UPDATE es relativo y con piso en 0: una venta o compra concurrente
     * entre medias no se pierde, y `stock_anterior` se deriva del valor real
     * posterior en vez de una lectura previa que pudo quedar vieja.
     *
     * @return array{ok: bool, stock_posterior?: int, error?: string}
     */
    public function registerInTransaction(int $idProducto, string $tipo, int $cantidad, string $motivo): array
    {
        if ($cantidad < 1 || !in_array($tipo, ['entrada', 'salida'], true)) {
            return ['ok' => false, 'error' => 'Datos de ajuste inválidos'];
        }

        $delta = ($tipo === 'entrada') ? $cantidad : -$cantidad;

        $stmt = $this->db->prepare(
            "UPDATE tb_almacen SET stock = stock + ? WHERE id_producto = ? AND stock + ? >= 0"
        );
        $stmt->execute([$delta, $idProducto, $delta]);

        if ($stmt->rowCount() === 0) {
            $rows = $this->query("SELECT stock FROM tb_almacen WHERE id_producto = ?", [$idProducto]);

            return empty($rows)
                ? ['ok' => false, 'error' => 'Producto no encontrado']
                : ['ok' => false, 'error' => 'Stock insuficiente para realizar la salida'];
        }

        $rows            = $this->query("SELECT stock FROM tb_almacen WHERE id_producto = ?", [$idProducto]);
        $stock_posterior = (int)$rows[0]['stock'];
        $stock_actual    = $stock_posterior - $delta;

        $user           = Auth::user();
        $id_usuario     = $user['id_usuario'] ?? null;
        $usuario_nombre = trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? ''));

        $this->create([
            'id_producto'     => $idProducto,
            'tipo'            => $tipo,
            'cantidad'        => $cantidad,
            'stock_anterior'  => $stock_actual,
            'stock_posterior' => $stock_posterior,
            'motivo'          => $motivo,
            'id_usuario'      => $id_usuario,
            'usuario_nombre'  => $usuario_nombre,
        ]);

        ActivityLog::record(
            'stock_adjustment',
            'product',
            $idProducto,
            "Ajuste de stock: {$tipo} de {$cantidad} unidades",
            ['stock' => $stock_actual],
            ['stock' => $stock_posterior]
        );

        return ['ok' => true, 'stock_posterior' => $stock_posterior];
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

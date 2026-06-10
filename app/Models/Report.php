<?php

namespace App\Models;

use App\Core\Database;

class Report
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    // ── Ventas ────────────────────────────────────────────────────────────────

    /**
     * Lista de ventas en el rango dado. Si $usuarioId > 0 filtra por vendedor.
     */
    public function salesByPeriod(string $desde, string $hasta, int $usuarioId = 0): array
    {
        $params = [$desde, $hasta];
        $scope  = '';
        if ($usuarioId > 0) {
            $scope = ' AND v.usuario_id = ?';
            $params[] = $usuarioId;
        }

        $stmt = $this->pdo->prepare("
            SELECT v.venta_id, v.numero_venta, v.fyh_creacion,
                   CONCAT(c.nombre,' ',c.apellido) AS cliente,
                   CONCAT(u.nombre,' ',u.apellido) AS vendedor,
                   v.monto_total
            FROM tb_ventas v
            LEFT JOIN tb_clientes  c ON c.cliente_id  = v.cliente_id
            LEFT JOIN tb_usuarios  u ON u.usuario_id  = v.usuario_id
            WHERE v.fyh_creacion BETWEEN ? AND ?
            {$scope}
            ORDER BY v.fyh_creacion DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Totalizadores del reporte de ventas.
     */
    public function salesTotals(string $desde, string $hasta, int $usuarioId = 0): array
    {
        $params = [$desde, $hasta];
        $scope  = '';
        if ($usuarioId > 0) {
            $scope = ' AND v.usuario_id = ?';
            $params[] = $usuarioId;
        }

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)                        AS num_ventas,
                   COALESCE(SUM(v.monto_total), 0) AS total_ingresos,
                   COALESCE(AVG(v.monto_total), 0) AS ticket_promedio
            FROM tb_ventas v
            WHERE v.fyh_creacion BETWEEN ? AND ?
            {$scope}
        ");
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // ── Compras ───────────────────────────────────────────────────────────────

    public function purchasesByPeriod(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.compra_id, c.numero_compra, c.fyh_creacion,
                   p.nombre AS proveedor,
                   CONCAT(u.nombre,' ',u.apellido) AS registrado_por,
                   c.monto_total
            FROM tb_compras c
            LEFT JOIN tb_proveedores p ON p.proveedor_id = c.proveedor_id
            LEFT JOIN tb_usuarios    u ON u.usuario_id   = c.usuario_id
            WHERE c.fyh_creacion BETWEEN ? AND ?
            ORDER BY c.fyh_creacion DESC
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function purchasesTotals(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)                        AS num_compras,
                   COALESCE(SUM(c.monto_total), 0) AS total_egresos
            FROM tb_compras c
            WHERE c.fyh_creacion BETWEEN ? AND ?
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // ── Top Productos ─────────────────────────────────────────────────────────

    private const ORDER_MAP = [
        'cantidad' => 'unidades_vendidas',
        'ingresos' => 'ingresos',
    ];
    private const TOP_WHITELIST = [5, 10, 20, 50];

    /**
     * @param int    $top       Cantidad de resultados (whitelist: 5,10,20,50; default 10).
     * @param string $orden     'cantidad'|'ingresos' (default 'cantidad').
     * @param int    $categoria Filtra por categoría si > 0.
     * @param int    $usuarioId Scope vendedor si > 0.
     */
    public function topProducts(
        string $desde,
        string $hasta,
        int    $top       = 10,
        string $orden     = 'cantidad',
        int    $categoria = 0,
        int    $usuarioId = 0
    ): array {
        $top   = in_array($top, self::TOP_WHITELIST, true) ? $top : 10;
        $orderCol = self::ORDER_MAP[$orden] ?? 'unidades_vendidas';

        $params  = [$desde, $hasta];
        $catSql  = '';
        $scopeSql = '';

        if ($categoria > 0) {
            $catSql = ' AND a.id_categoria = ?';
            $params[] = $categoria;
        }
        if ($usuarioId > 0) {
            $scopeSql = ' AND v.usuario_id = ?';
            $params[] = $usuarioId;
        }

        $params[] = $top;

        $stmt = $this->pdo->prepare("
            SELECT a.producto_id, a.nombre,
                   cat.nombre AS categoria,
                   SUM(ca.cantidad) AS unidades_vendidas,
                   SUM(ca.cantidad * ca.precio_unitario) AS ingresos
            FROM tb_carrito ca
            JOIN tb_ventas    v   ON v.venta_id    = ca.venta_id
            JOIN tb_almacen   a   ON a.producto_id = ca.producto_id
            LEFT JOIN tb_categorias cat ON cat.categoria_id = a.id_categoria
            WHERE v.fyh_creacion BETWEEN ? AND ?
            {$catSql}
            {$scopeSql}
            GROUP BY a.producto_id, a.nombre, cat.nombre
            ORDER BY {$orderCol} DESC
            LIMIT ?
        ");
        $stmt->bindValue(count($params), $top, \PDO::PARAM_INT);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── Clientes ──────────────────────────────────────────────────────────────

    public function clientsByPeriod(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.cliente_id,
                   CONCAT(c.nombre,' ',c.apellido)    AS cliente,
                   c.nit_ci, c.email,
                   COUNT(v.venta_id)                  AS num_compras,
                   COALESCE(SUM(v.monto_total), 0)    AS monto_acumulado,
                   MAX(v.fyh_creacion)                AS ultima_compra
            FROM tb_clientes c
            JOIN tb_ventas v ON v.cliente_id = c.cliente_id
            WHERE v.fyh_creacion BETWEEN ? AND ?
            GROUP BY c.cliente_id, c.nombre, c.apellido, c.nit_ci, c.email
            ORDER BY monto_acumulado DESC
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

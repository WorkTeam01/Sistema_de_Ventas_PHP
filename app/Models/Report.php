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

    public function salesByPeriod(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare("
            SELECT v.id_venta, v.nro_venta, v.fyh_creacion,
                   c.nombre_cliente AS cliente,
                   v.total_pagado
            FROM tb_ventas v
            LEFT JOIN tb_clientes c ON c.id_cliente = v.id_cliente
            WHERE v.fyh_creacion BETWEEN ? AND ?
            ORDER BY v.fyh_creacion DESC
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function salesTotals(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)                          AS num_ventas,
                   COALESCE(SUM(v.total_pagado), 0)  AS total_ingresos,
                   COALESCE(AVG(v.total_pagado), 0)  AS ticket_promedio
            FROM tb_ventas v
            WHERE v.fyh_creacion BETWEEN ? AND ?
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    // ── Compras ───────────────────────────────────────────────────────────────

    public function purchasesByPeriod(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id_compra, c.nro_compra, c.fecha_compra,
                   p.nombre_proveedor AS proveedor,
                   u.nombres           AS registrado_por,
                   (c.precio_compra * c.cantidad) AS monto_total
            FROM tb_compras c
            LEFT JOIN tb_proveedores p ON p.id_proveedor = c.id_proveedor
            LEFT JOIN tb_usuarios    u ON u.id_usuario   = c.id_usuario
            WHERE c.fyh_creacion BETWEEN ? AND ?
            ORDER BY c.fyh_creacion DESC
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function purchasesTotals(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)                                        AS num_compras,
                   COALESCE(SUM(c.precio_compra * c.cantidad), 0) AS total_egresos
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
     * @param int    $top       Whitelist: 5, 10, 20, 50 — default 10.
     * @param string $orden     'cantidad'|'ingresos' — default 'cantidad'.
     * @param int    $categoria Filtra por categoría si > 0.
     */
    public function topProducts(
        string $desde,
        string $hasta,
        int    $top       = 10,
        string $orden     = 'cantidad',
        int    $categoria = 0
    ): array {
        $top      = in_array($top, self::TOP_WHITELIST, true) ? $top : 10;
        $orderCol = self::ORDER_MAP[$orden] ?? 'unidades_vendidas';

        $catSql = '';
        $catParam = null;
        if ($categoria > 0) {
            $catSql   = ' AND a.id_categoria = ?';
            $catParam = $categoria;
        }

        $stmt = $this->pdo->prepare("
            SELECT a.id_producto, a.nombre,
                   cat.nombre_categoria AS categoria,
                   SUM(ca.cantidad)                          AS unidades_vendidas,
                   SUM(ca.cantidad * a.precio_venta)         AS ingresos
            FROM tb_carrito ca
            JOIN tb_ventas    v   ON v.nro_venta   = ca.nro_venta
            JOIN tb_almacen   a   ON a.id_producto = ca.id_producto
            LEFT JOIN tb_categorias cat ON cat.id_categoria = a.id_categoria
            WHERE v.fyh_creacion BETWEEN ? AND ?
            {$catSql}
            GROUP BY a.id_producto, a.nombre, cat.nombre_categoria
            ORDER BY {$orderCol} DESC
            LIMIT ?
        ");

        $pos = 1;
        $stmt->bindValue($pos++, $desde);
        $stmt->bindValue($pos++, $hasta);
        if ($catParam !== null) {
            $stmt->bindValue($pos++, $catParam, \PDO::PARAM_INT);
        }
        $stmt->bindValue($pos, $top, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── Clientes ──────────────────────────────────────────────────────────────

    public function clientsByPeriod(string $desde, string $hasta): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id_cliente,
                   c.nombre_cliente                        AS cliente,
                   c.nit_ci_cliente                        AS nit_ci,
                   c.email_cliente                         AS email,
                   COUNT(v.id_venta)                       AS num_compras,
                   COALESCE(SUM(v.total_pagado), 0)        AS monto_acumulado,
                   MAX(v.fyh_creacion)                     AS ultima_compra
            FROM tb_clientes c
            JOIN tb_ventas v ON v.id_cliente = c.id_cliente
            WHERE v.fyh_creacion BETWEEN ? AND ?
            GROUP BY c.id_cliente, c.nombre_cliente, c.nit_ci_cliente, c.email_cliente
            ORDER BY monto_acumulado DESC
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\Money;
use App\Helpers\MonthSeries;

/**
 * Modelo para la tabla tb_ventas.
 *
 * Las operaciones de escritura son transaccionales porque afectan tb_almacen (stock)
 * y tb_carrito. El orden de eliminación es crítico: tb_ventas (hija) antes de tb_carrito
 * (referenciada), debido a la FK tb_ventas.nro_venta → tb_carrito.nro_venta.
 */
class Sale extends Model
{
    /** Máximo de tb_ventas.total_pagado (DECIMAL(10,2)) en céntimos; SQLite no lo impone. */
    private const MAX_TOTAL_CENTS = 9999999999;

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
                 LEFT JOIN tb_clientes c ON v.id_cliente = c.id_cliente";
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
                    c.email_cliente, c.id_cliente AS id_cliente_rel,
                    u.nombres AS nombre_vendedor
             FROM tb_ventas v
             LEFT JOIN tb_clientes c ON v.id_cliente = c.id_cliente
             LEFT JOIN tb_usuarios u ON v.id_usuario = u.id_usuario
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

        $payments = (new SalePayment())->byVenta((int)$sale['id_venta']);
        $sale['payments'] = $payments;
        $sale['vuelto'] = SalePayment::vueltoFor((float)$sale['total_pagado'], $payments);

        return $sale;
    }

    /**
     * Inserta la cabecera de venta, sus líneas de pago y decrementa el stock,
     * todo en una sola transacción (sin ventana donde la venta exista sin pagos).
     *
     * Idempotente: si el mismo usuario reenvía un nro_venta ya cobrado devuelve esa venta con
     * `replayed` sin tocar stock ni precios; si es de otro usuario, `forbidden`. Un cobro nuevo exige
     * que el nro_venta sea una reserva abierta del usuario, y la libera dentro de la misma transacción.
     *
     * @param array $data    Datos de la venta: nro_venta, id_cliente (total_pagado se ignora).
     * @param array $payments Líneas: id_metodo_pago, monto.
     *                        Dos entradas del mismo método se suman en una línea (UNIQUE).
     * @param int   $userId  Usuario que cobra; queda como vendedor de la venta.
     * @return array{ok: bool, id_venta: ?int, vuelto: float, replayed: bool, error: ?string, faltante: ?float}
     *         error: empty_cart|total_cero|total_excede|sin_metodos|invalid_payment|faltante|exceso_sin_efectivo|
     *         vuelto_excede_efectivo|forbidden|generic
     */
    public function storeWithStock(array $data, array $payments, int $userId): array
    {
        $db = $this->db;
        $fail = static fn(string $error, ?float $faltante = null): array => [
            'ok'          => false,
            'id_venta'    => null,
            'vuelto'      => 0.0,
            'replayed'    => false,
            'error'       => $error,
            'faltante'    => $faltante,
        ];

        try {
            $db->beginTransaction();

            $existing = $this->findFinalized((int)$data['nro_venta']);
            if ($existing !== null) {
                $db->rollBack();
                return $this->replayResult($existing, $userId, $fail);
            }

            if (!(new PosReservation())->isOpenFor((int)$data['nro_venta'], $userId, true)) {
                $db->rollBack();
                return $fail('forbidden');
            }

            // Verificar carrito no vacío
            $stmt = $db->prepare("SELECT COUNT(*) FROM tb_carrito WHERE nro_venta = ?");
            $stmt->execute([$data['nro_venta']]);
            if ((int)$stmt->fetchColumn() === 0) {
                $db->rollBack();
                return $fail('empty_cart');
            }

            // Persistir precio de venta actual en cada ítem del carrito
            $persistPrice = $db->prepare(
                "UPDATE tb_carrito SET precio_unitario = (SELECT precio_venta FROM tb_almacen WHERE id_producto = tb_carrito.id_producto) WHERE nro_venta = ?"
            );
            $persistPrice->execute([$data['nro_venta']]);

            // Calcular total desde precios congelados (SIEMPRE server-side, ignora el POST)
            $totalsStmt = $db->prepare(
                "SELECT SUM(cantidad * precio_unitario) AS total
                 FROM tb_carrito WHERE nro_venta = ?"
            );
            $totalsStmt->execute([$data['nro_venta']]);
            $totalCents = Money::toCents($totalsStmt->fetchColumn() ?: 0);
            if ($totalCents === null) {
                $db->rollBack();
                return $fail('generic');
            }
            if ($totalCents === 0) {
                $db->rollBack();
                return $fail('total_cero');
            }
            if ($totalCents > self::MAX_TOTAL_CENTS) {
                $db->rollBack();
                return $fail('total_excede');
            }

            // Mapa de métodos: id → (tipo, activo); catálogo vacío o todo desactivado → sin_metodos
            $methodRows = $db->query(
                "SELECT id_metodo_pago, tipo, activo FROM tb_metodos_pago"
            )->fetchAll(\PDO::FETCH_ASSOC);
            $map = [];
            $activeCount = 0;
            foreach ($methodRows as $row) {
                $id = (int)$row['id_metodo_pago'];
                $map[$id] = ['tipo' => $row['tipo'], 'activo' => (int)$row['activo']];
                if ($map[$id]['activo'] === 1) {
                    $activeCount++;
                }
            }
            if ($activeCount === 0) {
                $db->rollBack();
                return $fail('sin_metodos');
            }

            // Los montos se normalizan a céntimos al entrar y todo se compara en enteros; las líneas
            // del mismo método se suman ya redondeadas, que es lo que realmente se guarda
            $merged = [];
            foreach ($payments as $payment) {
                $idMetodo = (int)($payment['id_metodo_pago'] ?? 0);
                $cents = Money::toCents($payment['monto'] ?? '');
                if ($cents === null || $cents <= 0 || !isset($map[$idMetodo]) || $map[$idMetodo]['activo'] !== 1) {
                    $db->rollBack();
                    return $fail('invalid_payment');
                }
                $merged[$idMetodo] = ($merged[$idMetodo] ?? 0) + $cents;
            }
            if ($merged === []) {
                $db->rollBack();
                return $fail('invalid_payment');
            }

            $sumCents = array_sum($merged);

            if ($sumCents < $totalCents) {
                $db->rollBack();
                return $fail('faltante', ($totalCents - $sumCents) / 100.0);
            }

            // El vuelto sale siempre del efectivo recibido
            $changeCents = $sumCents - $totalCents;
            if ($changeCents > 0) {
                $cashCents = 0;
                foreach ($merged as $idMetodo => $cents) {
                    if ($map[$idMetodo]['tipo'] === 'efectivo') {
                        $cashCents += $cents;
                    }
                }
                if ($cashCents === 0) {
                    $db->rollBack();
                    return $fail('exceso_sin_efectivo');
                }
                if ($changeCents > $cashCents) {
                    $db->rollBack();
                    return $fail('vuelto_excede_efectivo');
                }
            }

            // INSERT cabecera de venta (id_cliente opcional → NULL = venta sin cliente)
            $idCliente = isset($data['id_cliente']) && (int)$data['id_cliente'] > 0
                ? (int)$data['id_cliente']
                : null;
            try {
                $db->prepare(
                    "INSERT INTO tb_ventas (nro_venta, id_cliente, id_usuario, total_pagado) VALUES (?, ?, ?, ?)"
                )->execute([
                    $data['nro_venta'],
                    $idCliente,
                    $userId,
                    Money::fromCents($totalCents),
                ]);
            } catch (\PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                // Otro envío del mismo cobro llegó antes (doble clic, segunda pestaña): UNIQUE(nro_venta)
                $db->rollBack();
                $winner = $this->findFinalized((int)$data['nro_venta']);

                return $winner !== null ? $this->replayResult($winner, $userId, $fail) : $fail('generic');
            }
            $idVenta = (int)$db->lastInsertId();

            // INSERT una línea por método (monto ya sumado; vuelto nunca se persiste)
            $insertPago = $db->prepare(
                "INSERT INTO tb_pagos (id_venta, id_metodo_pago, monto) VALUES (?, ?, ?)"
            );
            foreach ($merged as $idMetodo => $cents) {
                $insertPago->execute([$idVenta, $idMetodo, Money::fromCents($cents)]);
            }

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
                    return $fail('generic');
                }
            }

            (new PosReservation())->release((int)$data['nro_venta']);

            $db->commit();
            return [
                'ok'       => true,
                'id_venta' => $idVenta,
                'vuelto'   => $changeCents / 100.0,
                'replayed' => false,
                'error'    => null,
                'faltante' => null,
            ];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return $fail('generic');
        }
    }

    /** @return array{id_venta: int, id_usuario: ?int}|null */
    private function findFinalized(int $nroVenta): ?array
    {
        $rows = $this->query('SELECT id_venta, id_usuario FROM tb_ventas WHERE nro_venta = ?', [$nroVenta]);

        return $rows[0] ?? null;
    }

    /**
     * Respuesta del reenvío de un cobro ya registrado: la venta original con el vuelto recalculado
     * desde tb_pagos, o forbidden si la venta es de otro usuario.
     */
    private function replayResult(array $sale, int $userId, callable $fail): array
    {
        if ((int)$sale['id_usuario'] !== $userId) {
            return $fail('forbidden');
        }

        $idVenta = (int)$sale['id_venta'];
        $total = (float)$this->query('SELECT total_pagado FROM tb_ventas WHERE id_venta = ?', [$idVenta])[0]['total_pagado'];

        return [
            'ok'       => true,
            'id_venta' => $idVenta,
            'vuelto'   => SalePayment::vueltoFor($total, (new SalePayment())->byVenta($idVenta)),
            'replayed' => true,
            'error'    => null,
            'faltante' => null,
        ];
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
     * Ventas agrupadas por mes — últimos N meses, desde el primer día del mes más antiguo de
     * MonthSeries (la misma serie que rotula el gráfico). Filtra por usuario si se indica $userId.
     *
     * @param \DateTimeImmutable|null $base Fecha de referencia; hoy si es null.
     * @return array Lista de ['mes' => 'YYYY-MM', 'total' => float]
     */
    public function totalsByMonth(int $months, ?int $userId = null, ?\DateTimeImmutable $base = null): array
    {
        $start = MonthSeries::lastMonths(max(1, $months), $base)[0] . '-01';
        $isSqlite = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $month = $isSqlite ? "strftime('%Y-%m', fyh_creacion)" : "DATE_FORMAT(fyh_creacion, '%Y-%m')";
        $returnMonth = str_replace('fyh_creacion', 'dv.fyh_creacion', $month);
        $sql = "SELECT mes, COALESCE(SUM(total), 0) AS total
                FROM (
                    SELECT $month AS mes, SUM(v.total_pagado) AS total
                    FROM tb_ventas v
                    WHERE v.fyh_creacion >= ?"
            . ($userId !== null ? " AND v.id_usuario = ?" : '') . "
                    GROUP BY $month
                    UNION ALL
                    SELECT $returnMonth AS mes, -SUM(dv.monto) AS total
                    FROM tb_devoluciones dv
                    INNER JOIN tb_ventas v2 ON v2.id_venta = dv.id_venta
                    WHERE dv.fyh_creacion >= ?"
            . ($userId !== null ? " AND v2.id_usuario = ?" : '') . "
                    GROUP BY $returnMonth
                ) periods
                GROUP BY mes ORDER BY mes ASC";
        $params = $userId !== null ? [$start, $userId, $start, $userId] : [$start, $start];
        return $this->query($sql, $params);
    }

    /** Últimas N ventas con nombre del cliente. Filtra por usuario si se indica $userId. */
    public function latest(int $limit = 5, ?int $userId = null): array
    {
        $limit = (int)$limit;
        $sql = "SELECT v.id_venta, v.nro_venta, c.nombre_cliente, v.total_pagado, v.fyh_creacion
                FROM tb_ventas v
                LEFT JOIN tb_clientes c ON v.id_cliente = c.id_cliente";
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
     * `total_pagado` es el monto real de la venta; en ventas históricas puede diferir de la suma de las
     * líneas a precio congelado, y esa diferencia (positiva o negativa) sale como `ajuste` para que la
     * tabla sume `total_pagado`. Sin $totalPagado (carrito en curso) no hay ajuste.
     *
     * @param array $items Ítems del carrito (deben incluir 'cantidad' y 'precio_venta').
     * @param float|null $totalPagado Monto guardado de la venta, si ya está finalizada.
     * @return array{precio_total: float, cantidad_total: int, total_unitarios: float, ajuste: float, total_pagado: float}
     */
    public function computeInvoiceTotals(array $items, ?float $totalPagado = null): array
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

        // Diferencia en céntimos para que el ruido de float (3 × 11.10) no se informe como ajuste
        $adjustmentCents = $totalPagado === null
            ? 0
            : (Money::toCents(max($totalPagado, 0.0)) ?? 0) - (Money::toCents(max($precioTotal, 0.0)) ?? 0);

        return [
            'precio_total' => $precioTotal,
            'cantidad_total' => $cantidadTotal,
            'total_unitarios' => $totalUnitarios,
            'ajuste' => $adjustmentCents / 100.0,
            'total_pagado' => $totalPagado ?? $precioTotal,
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

    /**
     * Elimina una venta y repone su stock. Dentro de la transacción vuelve a comprobar que no tenga
     * devoluciones: el bloqueo de la fila de la venta serializa el borrado con el registro de una
     * devolución concurrente, así no quedan devoluciones huérfanas.
     *
     * @return array{ok: bool, error: ?string} error: not_found|has_returns|generic
     */
    public function destroyWithStock(int $id): array
    {
        $db = $this->db;
        try {
            $db->beginTransaction();

            $lock = $db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
            $stmt = $db->prepare("SELECT nro_venta FROM tb_ventas WHERE id_venta = ?" . $lock);
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'not_found'];
            }
            $nroVenta = (int)$row['nro_venta'];

            if ($this->isReferenced($id)) {
                $db->rollBack();
                return ['ok' => false, 'error' => 'has_returns'];
            }

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
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['ok' => false, 'error' => 'generic'];
        }
    }
}

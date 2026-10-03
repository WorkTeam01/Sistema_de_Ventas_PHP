<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\Money;

/**
 * Modelo para la tabla tb_pagos (líneas de pago por venta).
 *
 * El vuelto nunca se persiste como línea: se deriva con vueltoFor().
 */
class SalePayment extends Model
{
    protected string $table = 'tb_pagos';
    protected string $primaryKey = 'id_pago';

    /**
     * Líneas de pago de una venta, con nombre y tipo del método.
     *
     * @param int $idVenta ID de la venta.
     * @return array Filas con p.*, m.nombre, m.tipo; vacío si no hay pagos.
     */
    public function byVenta(int $idVenta): array
    {
        return $this->query(
            "SELECT p.*, m.nombre, m.tipo
             FROM {$this->table} p
             INNER JOIN tb_metodos_pago m ON m.id_metodo_pago = p.id_metodo_pago
             WHERE p.id_venta = ?
             ORDER BY p.id_pago ASC",
            [$idVenta]
        );
    }

    /**
     * Vuelto puro: max(0, round(suma de montos, 2) − total).
     * Nunca se persiste como línea de pago.
     *
     * @param float $total    Total de la venta.
     * @param array $payments Líneas con clave 'monto'.
     * @return float Vuelto (0 cuando la suma ≤ total).
     */
    public static function vueltoFor(float $total, array $payments): float
    {
        $sumCents = 0;
        foreach ($payments as $payment) {
            $sumCents += Money::toCents($payment['monto'] ?? 0) ?? 0;
        }

        return max(0, $sumCents - (Money::toCents(max($total, 0.0)) ?? 0)) / 100.0;
    }
}

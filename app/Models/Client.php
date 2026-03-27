<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_clientes.
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class Client extends Model
{
    protected string $table      = 'tb_clientes';
    protected string $primaryKey = 'id_cliente';

    /**
     * Indica si el cliente está referenciado en alguna venta registrada.
     *
     * @param int|string $id ID del cliente.
     * @return bool true si existe al menos una venta asociada.
     */
    public function isReferenced(int|string $id): bool
    {
        $result = $this->query(
            "SELECT COUNT(*) AS total FROM tb_ventas WHERE id_cliente = ?",
            [$id]
        );
        return ($result[0]['total'] ?? 0) > 0;
    }
}

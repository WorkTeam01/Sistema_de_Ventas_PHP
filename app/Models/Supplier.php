<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_proveedores.
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class Supplier extends Model
{
    protected string $table      = 'tb_proveedores';
    protected string $primaryKey = 'id_proveedor';

    /**
     * Indica si el proveedor está referenciado en alguna compra registrada.
     *
     * @param int|string $id ID del proveedor.
     * @return bool true si existe al menos una compra asociada.
     */
    public function isReferenced(int|string $id): bool
    {
        $result = $this->query(
            "SELECT COUNT(*) AS total FROM tb_compras WHERE id_proveedor = ?",
            [$id]
        );
        return ($result[0]['total'] ?? 0) > 0;
    }
}

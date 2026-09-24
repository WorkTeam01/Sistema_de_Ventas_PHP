<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_metodos_pago (catálogo de formas de cobro).
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class PaymentMethod extends Model
{
    protected string $table = 'tb_metodos_pago';
    protected string $primaryKey = 'id_metodo_pago';

    /**
     * Devuelve solo los métodos activos (para el paso Cobro del POS).
     *
     * @return array Métodos con activo = 1, ordenados por nombre.
     */
    public function active(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY nombre"
        );
    }

    /**
     * Verifica si el nombre del método ya existe en la base de datos.
     *
     * @param string   $nombre    Nombre a verificar.
     * @param int|null $excludeId ID del método actual para excluir al editar.
     * @return bool Verdadero si ya existe, falso si está disponible.
     */
    public function nameExists(string $nombre, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->table} WHERE nombre = ?";
        $params = [$nombre];

        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * Indica si el método está referenciado por al menos una línea de pago.
     * Un método referenciado no se puede eliminar; solo desactivar (FR-2).
     *
     * @param int|string $id ID del método de pago.
     * @return bool true si existe al menos una línea en tb_pagos.
     */
    public function isReferenced(int|string $id): bool
    {
        $result = $this->query(
            "SELECT COUNT(*) AS total FROM tb_pagos WHERE id_metodo_pago = ?",
            [$id]
        );
        return ($result[0]['total'] ?? 0) > 0;
    }
}

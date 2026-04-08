<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_roles.
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class Role extends Model
{
    protected string $table = 'tb_roles';
    protected string $primaryKey = 'id_rol';

    /**
     * Verifica si el nombre del rol ya existe en la base de datos.
     *
     * @param string $name Nombre a verificar.
     * @param int|null $excludeId ID del rol actual para excluir al editar.
     * @return bool Verdadero si ya existe, falso si está disponible.
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE rol = ?";
        $params = [$name];

        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return $result[0]['count'] > 0;
    }
}

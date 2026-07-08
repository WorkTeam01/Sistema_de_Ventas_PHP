<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_permisos.
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class Permission extends Model
{
    protected string $table = 'tb_permisos';
    protected string $primaryKey = 'id_permiso';

    /**
     * Verifica si la clave del permiso ya existe en la base de datos.
     *
     * @param string $clave Clave a verificar.
     * @param int|null $excludeId ID del permiso actual para excluir al editar.
     * @return bool Verdadero si ya existe, falso si está disponible.
     */
    public function claveExists(string $clave, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE clave = ?";
        $params = [$clave];

        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return $result[0]['count'] > 0;
    }

    /**
     * Devuelve todos los permisos agrupados por módulo.
     *
     * @return array Arreglo asociativo modulo => lista de permisos.
     */
    public function allGroupedByModulo(): array
    {
        $rows = $this->query("SELECT * FROM {$this->table} ORDER BY modulo, clave");

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['modulo']][] = $row;
        }

        return $grouped;
    }
}

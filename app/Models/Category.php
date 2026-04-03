<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_categorias.
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class Category extends Model
{
    protected string $table      = 'tb_categorias';
    protected string $primaryKey = 'id_categoria';

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) as count FROM {$this->table} WHERE nombre_categoria = ?";
        $params = [$name];

        if ($excludeId) {
            $sql     .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return $result[0]['count'] > 0;
    }
}

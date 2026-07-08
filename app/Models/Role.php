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

    /**
     * Devuelve los IDs de permisos asignados a un rol.
     *
     * @param int $roleId ID del rol.
     * @return array Lista de IDs de permiso (int).
     */
    public function getAssignedPermissionIds(int $roleId): array
    {
        $rows = $this->query('SELECT id_permiso FROM tb_rol_permiso WHERE id_rol = ?', [$roleId]);
        return array_map(static fn (array $row) => (int) $row['id_permiso'], $rows);
    }

    /**
     * Reemplaza el conjunto completo de permisos asignados a un rol
     * e incrementa permisos_version para invalidar la caché de sesión.
     *
     * @param int   $roleId        ID del rol.
     * @param array $permissionIds IDs de permisos a asignar.
     * @return bool True si la operación fue exitosa.
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare('DELETE FROM tb_rol_permiso WHERE id_rol = ?');
            $stmt->execute([$roleId]);

            $stmt = $this->db->prepare('INSERT INTO tb_rol_permiso (id_rol, id_permiso) VALUES (?, ?)');
            foreach ($permissionIds as $permissionId) {
                $stmt->execute([$roleId, (int) $permissionId]);
            }

            $stmt = $this->db->prepare('UPDATE tb_roles SET permisos_version = permisos_version + 1 WHERE id_rol = ?');
            $stmt->execute([$roleId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }
}

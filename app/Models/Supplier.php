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
    protected string $table = 'tb_proveedores';
    protected string $primaryKey = 'id_proveedor';

    /**
     * Verifica si el nombre de empresa ya existe en la base de datos.
     *
     * @param string $empresa Nombre de empresa a verificar.
     * @param int|null $excludeId ID del proveedor actual para excluir al editar.
     * @return bool Verdadero si ya existe, falso si está disponible.
     */
    public function nameExists(string $empresa, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE empresa = ?";
        $params = [$empresa];

        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return $result[0]['count'] > 0;
    }

    /**
     * Crea un nuevo proveedor normalizando los campos opcionales.
     * Telefono y email vacíos se almacenan como NULL.
     *
     * @return int|false ID del proveedor insertado, o false si hubo error.
     */
    public function createSupplier(
        string $nombre,
        string $empresa,
        string $celular,
        string $direccion,
        ?string $telefono,
        ?string $email
    ): int|false {
        return $this->create([
            'nombre_proveedor' => $nombre,
            'empresa'          => $empresa,
            'celular'          => $celular,
            'telefono'         => ($telefono !== null && trim($telefono) !== '') ? trim($telefono) : null,
            'email'            => ($email    !== null && trim($email)    !== '') ? trim($email)    : null,
            'direccion'        => $direccion,
        ]);
    }

    /**
     * Actualiza un proveedor existente normalizando los campos opcionales.
     * Telefono y email vacíos se almacenan como NULL.
     *
     * @return bool True si la actualización fue exitosa.
     */
    public function updateSupplier(
        int $id,
        string $nombre,
        string $empresa,
        string $celular,
        string $direccion,
        ?string $telefono,
        ?string $email
    ): bool {
        return $this->update($id, [
            'nombre_proveedor' => $nombre,
            'empresa'          => $empresa,
            'celular'          => $celular,
            'telefono'         => ($telefono !== null && trim($telefono) !== '') ? trim($telefono) : null,
            'email'            => ($email    !== null && trim($email)    !== '') ? trim($email)    : null,
            'direccion'        => $direccion,
        ]);
    }

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

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
    protected string $table = 'tb_clientes';
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

    public function countNewThisMonth(): int
    {
        $rows = $this->query(
            "SELECT COUNT(*) AS total FROM tb_clientes
             WHERE YEAR(fyh_creacion) = YEAR(CURDATE())
               AND MONTH(fyh_creacion) = MONTH(CURDATE())"
        );
        return (int)$rows[0]['total'];
    }

    /**
     * Verifica si el NIT/CI ya existe en la base de datos.
     *
     * @param string $nitCi NIT/CI a verificar.
     * @param int|null $excludeId ID del cliente actual para excluir al editar.
     * @return bool Verdadero si ya existe, falso si está disponible.
     */
    public function nitCiExists(string $nitCi, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE nit_ci_cliente = ?";
        $params = [$nitCi];

        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return $result[0]['count'] > 0;
    }

    /**
     * Valida que el correo electrónico tenga formato correcto.
     *
     * @param string $email Email a validar.
     * @return bool True si el formato es válido.
     */
    public function isValidEmail(string $email): bool
    {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Verifica si el correo electrónico ya existe en la base de datos.
     *
     * @param string $email Email a verificar.
     * @param int|null $excludeId ID del cliente actual para excluir al editar.
     * @return bool Verdadero si ya existe, falso si está disponible.
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email_cliente = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params);
        return $result[0]['count'] > 0;
    }
}

<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_usuarios.
 *
 * Extiende Model con métodos específicos de autenticación,
 * consultas con join de rol y gestión de contraseñas.
 */
class User extends Model
{
    protected string $table = 'tb_usuarios';
    protected string $primaryKey = 'id_usuario';

    /**
     * Busca un usuario por su dirección de email.
     *
     * @param string $email Email a buscar.
     * @return array|false Datos del usuario o false si no existe.
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        return $stmt->fetch();
    }

    /**
     * Verifica las credenciales de login comparando el password con el hash almacenado.
     *
     * @param string $email Email del usuario.
     * @param string $plainPassword Contraseña en texto plano.
     * @return array|false Datos del usuario si las credenciales son correctas, false si no.
     */
    public function verifyCredentials(string $email, string $plainPassword): array|false
    {
        $user = $this->findByEmail($email);

        if (!$user || !isset($user['password_user'])) {
            return false;
        }

        if (!password_verify($plainPassword, $user['password_user'])) {
            return false;
        }

        return $user;
    }

    /**
     * Devuelve todos los usuarios con el nombre de su rol (JOIN con tb_roles),
     * ordenados por id_usuario descendente.
     *
     * @return array Lista de usuarios con columnas id_usuario, nombres, email, id_rol, rol.
     */
    public function findAllWithRole(): array
    {
        $stmt = $this->db->query(
            "SELECT us.id_usuario, us.nombres, us.email, us.id_rol, rol.rol
             FROM tb_usuarios us
             INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol
             ORDER BY us.id_usuario DESC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Busca un usuario por ID incluyendo el nombre de su rol.
     *
     * @param int $id ID del usuario.
     * @return array|false Datos del usuario con su rol, o false si no existe.
     */
    public function findWithRoleById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT us.id_usuario, us.nombres, us.email, us.id_rol, us.fyh_creacion, rol.rol
             FROM tb_usuarios us
             INNER JOIN tb_roles rol ON us.id_rol = rol.id_rol
             WHERE us.id_usuario = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /**
     * Devuelve todos los roles disponibles para poblar selectores de formulario.
     *
     * @return array Lista de roles con columnas id_rol y rol.
     */
    public function findAllRoles(): array
    {
        $stmt = $this->db->query("SELECT id_rol, rol FROM tb_roles ORDER BY id_rol");
        return $stmt->fetchAll();
    }

    /**
     * Verifica si un email ya está registrado, opcionalmente excluyendo un usuario específico.
     * Útil para validar unicidad tanto en creación como en edición.
     *
     * @param string $email Email a verificar.
     * @param int|null $excludeUserId ID del usuario a excluir de la búsqueda (para edición).
     * @return bool True si el email ya está en uso por otro usuario.
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        if ($excludeUserId !== null) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM $this->table WHERE email = ? AND id_usuario <> ?");
            $stmt->execute([$email, $excludeUserId]);
            return (int)$stmt->fetchColumn() > 0;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM $this->table WHERE email = ?");
        $stmt->execute([$email]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Crea un nuevo usuario con los datos proporcionados.
     *
     * @param string $nombres Nombre completo del usuario.
     * @param string $email Email del usuario.
     * @param int $idRol ID del rol asignado.
     * @param string $plainPassword Contraseña en texto plano; se hashea internamente.
     * @return bool True si la inserción fue exitosa.
     */
    public function createUser(string $nombres, string $email, int $idRol, string $plainPassword): bool
    {
        return $this->create([
                'nombres' => $nombres,
                'email' => $email,
                'id_rol' => $idRol,
                'password_user' => password_hash($plainPassword, PASSWORD_DEFAULT),
            ]) !== false;
    }

    /**
     * Indica si el usuario está referenciado en productos (tb_almacen) o compras (tb_compras).
     * Usado antes de eliminar para evitar violar restricciones de FK.
     *
     * @param int|string $id ID del usuario.
     * @return bool true si existe al menos un producto o compra asociado.
     */
    public function isReferenced(int|string $id): bool
    {
        $productos = $this->query(
            "SELECT COUNT(*) AS total FROM tb_almacen WHERE id_usuario = ?",
            [$id]
        );
        if (($productos[0]['total'] ?? 0) > 0) {
            return true;
        }

        $compras = $this->query(
            "SELECT COUNT(*) AS total FROM tb_compras WHERE id_usuario = ?",
            [$id]
        );
        return ($compras[0]['total'] ?? 0) > 0;
    }

    /**
     * Devuelve un desglose de cuántos registros tiene el usuario en cada tabla referenciada.
     *
     * @param int $id ID del usuario.
     * @return array{productos: int, compras: int}
     */
    public function getReferenceCount(int $id): array
    {
        $productos = $this->query(
            "SELECT COUNT(*) AS total FROM tb_almacen WHERE id_usuario = ?",
            [$id]
        );
        $compras = $this->query(
            "SELECT COUNT(*) AS total FROM tb_compras WHERE id_usuario = ?",
            [$id]
        );

        return [
            'productos' => (int)($productos[0]['total'] ?? 0),
            'compras' => (int)($compras[0]['total'] ?? 0),
        ];
    }

    /**
     * Actualiza nombre y email del propio usuario sin modificar su rol ni contraseña.
     *
     * @param int $id ID del usuario.
     * @param string $nombres Nuevo nombre completo.
     * @param string $email Nuevo email.
     * @return bool True si la actualización fue exitosa.
     */
    public function updateProfileInfo(int $id, string $nombres, string $email): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE $this->table
             SET nombres = ?, email = ?
             WHERE $this->primaryKey = ?"
        );
        return $stmt->execute([$nombres, $email, $id]);
    }

    /**
     * Actualiza únicamente la contraseña de un usuario.
     *
     * @param int $id ID del usuario.
     * @param string $plainPassword Nueva contraseña en texto plano; se hashea internamente.
     * @return bool True si la actualización fue exitosa.
     */
    public function updatePassword(int $id, string $plainPassword): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE $this->table
             SET password_user = ?
             WHERE $this->primaryKey = ?"
        );
        return $stmt->execute([password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
    }

    /**
     * Actualiza los datos de un usuario. Si $plainPassword es null, no modifica la contraseña.
     *
     * @param int $id ID del usuario a actualizar.
     * @param string $nombres Nuevo nombre completo.
     * @param string $email Nuevo email.
     * @param int $idRol Nuevo ID de rol.
     * @param string|null $plainPassword Nueva contraseña en texto plano, o null para no cambiarla.
     * @return bool True si la actualización fue exitosa.
     */
    public function updateUser(int $id, string $nombres, string $email, int $idRol, ?string $plainPassword = null): bool
    {
        if ($plainPassword === null) {
            $stmt = $this->db->prepare(
                "UPDATE $this->table
                 SET nombres = ?, email = ?, id_rol = ?
                 WHERE $this->primaryKey = ?"
            );

            return $stmt->execute([$nombres, $email, $idRol, $id]);
        }

        $stmt = $this->db->prepare(
            "UPDATE $this->table
             SET nombres = ?, email = ?, id_rol = ?, password_user = ?
             WHERE $this->primaryKey = ?"
        );

        return $stmt->execute([$nombres, $email, $idRol, password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
    }
}

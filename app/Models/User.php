<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuthSettings;

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

    public const MIN_PASSWORD_LENGTH = 8;

    /**
     * Normaliza un email (espacios y minúsculas). SQLite distingue mayúsculas y MariaDB no,
     * por eso se normaliza también al escribir.
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Valida el largo mínimo de una contraseña nueva. El login no la usa: las contraseñas
     * existentes de menos de 8 caracteres siguen siendo válidas.
     */
    public static function validatePassword(string $plainPassword): bool
    {
        return mb_strlen($plainPassword) >= self::MIN_PASSWORD_LENGTH;
    }

    /**
     * Comprueba la contraseña actual de un usuario (cambio de contraseña desde el perfil).
     */
    public function verifyCurrentPassword(int $id, string $plainPassword): bool
    {
        $stmt = $this->db->prepare("SELECT password_user FROM $this->table WHERE $this->primaryKey = ? LIMIT 1");
        $stmt->execute([$id]);
        $hash = $stmt->fetchColumn();

        return is_string($hash) && $hash !== '' && password_verify($plainPassword, $hash);
    }

    /**
     * Busca un usuario por su dirección de email.
     *
     * @param string $email Email a buscar.
     * @return array|false Datos del usuario o false si no existe.
     */
    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? LIMIT 1");
        $stmt->execute([self::normalizeEmail($email)]);

        return $stmt->fetch();
    }

    /** Hash de una contraseña aleatoria descartada: iguala el costo de verificar cuando el correo no existe. */
    private const DUMMY_PASSWORD_HASH = '$2y$10$V3T55HUtIGsDDzkvlusohekgLa1Fb/e.ryxzJXwfdg.EoiMndzbjS';

    /**
     * Compara la contraseña con la del usuario. Si el usuario no existe verifica igualmente contra un
     * hash ficticio, para que el tiempo de respuesta no delate qué correos están registrados.
     */
    public static function passwordMatches(?array $user, string $plainPassword): bool
    {
        $hasHash = is_array($user) && is_string($user['password_user'] ?? null) && $user['password_user'] !== '';
        $matches = password_verify($plainPassword, $hasHash ? $user['password_user'] : self::DUMMY_PASSWORD_HASH);

        return $hasHash && $matches;
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
            $stmt->execute([self::normalizeEmail($email), $excludeUserId]);
            return (int)$stmt->fetchColumn() > 0;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM $this->table WHERE email = ?");
        $stmt->execute([self::normalizeEmail($email)]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Crea un nuevo usuario con los datos proporcionados.
     *
     * @param string $nombres Nombre completo del usuario.
     * @param string $email Email del usuario.
     * @param int $idRol ID del rol asignado.
     * @param string $plainPassword Contraseña en texto plano; se hashea internamente.
     * @return int|false ID del usuario creado, o false si falló la inserción.
     */
    public function createUser(string $nombres, string $email, int $idRol, string $plainPassword): int|false
    {
        return $this->create([
            'nombres' => $nombres,
            'email' => self::normalizeEmail($email),
            'id_rol' => $idRol,
            'password_user' => password_hash($plainPassword, PASSWORD_DEFAULT),
        ]);
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
     * Indica si el usuario es el único cuyo rol concede `is_superadmin`: quitarle ese rol
     * dejaría el sistema sin nadie que gestione roles y permisos.
     *
     * @param int $id ID del usuario.
     */
    public function isLastSuperadminUser(int $id): bool
    {
        $rows = $this->query(
            "SELECT u.id_usuario
             FROM tb_usuarios u
             INNER JOIN tb_rol_permiso rp ON rp.id_rol = u.id_rol
             INNER JOIN tb_permisos p ON p.id_permiso = rp.id_permiso
             WHERE p.clave = 'is_superadmin'",
            []
        );
        $ids = array_map('intval', array_column($rows, 'id_usuario'));

        return $ids === [$id];
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
             SET nombres = ?, email = ?,
                 reset_token = CASE WHEN email <> ? THEN NULL ELSE reset_token END,
                 reset_token_expiracion = CASE WHEN email <> ? THEN NULL ELSE reset_token_expiracion END
             WHERE $this->primaryKey = ?"
        );
        $normalized = self::normalizeEmail($email);

        return $stmt->execute([$nombres, $normalized, $normalized, $normalized, $id]);
    }

    /**
     * Actualiza únicamente la contraseña de un usuario y cierra sus sesiones "recordarme" en todos los dispositivos.
     *
     * @param int $id ID del usuario.
     * @param string $plainPassword Nueva contraseña en texto plano; se hashea internamente.
     * @return bool True si la actualización fue exitosa.
     */
    public function updatePassword(int $id, string $plainPassword): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE $this->table
             SET password_user = ?, reset_token = NULL, reset_token_expiracion = NULL
             WHERE $this->primaryKey = ?"
        );
        $updated = $stmt->execute([password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
        if ($updated) {
            (new RememberToken())->revokeAllFor($id);
        }

        return $updated;
    }

    /**
     * Guarda el hash del token de restablecimiento con la vigencia configurada (RESET_TOKEN_MINUTES).
     * La expiración la calcula la BD para no depender del reloj de PHP.
     *
     * @param int $id_usuario ID del usuario.
     * @param string $tokenHash SHA-256 (hex) del token enviado por email.
     * @return bool True si la actualización fue exitosa.
     */
    public function storeResetToken(int $id_usuario, string $tokenHash): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE $this->table
             SET reset_token = ?, reset_token_expiracion = DATE_ADD(NOW(), INTERVAL " . AuthSettings::resetTokenMinutes() . " MINUTE)
             WHERE $this->primaryKey = ?"
        );
        return $stmt->execute([$tokenHash, $id_usuario]);
    }

    /**
     * Busca un usuario por el token de restablecimiento en claro (se compara por su hash)
     * verificando que no haya expirado.
     *
     * @param string $plainToken Token recibido en el enlace.
     * @return array|false Datos del usuario o false si el token es inválido o expirado.
     */
    public function findByResetToken(string $plainToken): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM $this->table
             WHERE reset_token = ? AND reset_token_expiracion > NOW()
             LIMIT 1"
        );
        $stmt->execute([hash('sha256', $plainToken)]);
        return $stmt->fetch();
    }

    /**
     * Limpia el token de restablecimiento después de usarlo.
     *
     * @param int $id_usuario ID del usuario.
     * @return bool True si la actualización fue exitosa.
     */
    public function clearResetToken(int $id_usuario): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE $this->table
             SET reset_token = NULL, reset_token_expiracion = NULL
             WHERE $this->primaryKey = ?"
        );
        return $stmt->execute([$id_usuario]);
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
                 SET nombres = ?, email = ?, id_rol = ?,
                     reset_token = CASE WHEN email <> ? THEN NULL ELSE reset_token END,
                 reset_token_expiracion = CASE WHEN email <> ? THEN NULL ELSE reset_token_expiracion END
                 WHERE $this->primaryKey = ?"
            );
            $normalized = self::normalizeEmail($email);

            return $stmt->execute([$nombres, $normalized, $idRol, $normalized, $normalized, $id]);
        }

        $stmt = $this->db->prepare(
            "UPDATE $this->table
             SET nombres = ?, email = ?, id_rol = ?, password_user = ?,
                 reset_token = NULL, reset_token_expiracion = NULL
             WHERE $this->primaryKey = ?"
        );

        $updated = $stmt->execute([$nombres, self::normalizeEmail($email), $idRol, password_hash($plainPassword, PASSWORD_DEFAULT), $id]);
        if ($updated) {
            (new RememberToken())->revokeAllFor($id);
        }

        return $updated;
    }
}

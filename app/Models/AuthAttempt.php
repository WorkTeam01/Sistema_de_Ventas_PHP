<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuthSettings;

/**
 * Intentos de login y solicitudes de restablecimiento por correo e IP.
 *
 * Cada intento se registra antes de verificar nada y se cuenta solo hasta su propio id:
 * en una ráfaga paralela solo los primeros por id llegan a verificar la contraseña.
 * Las ventanas de tiempo usan el reloj de la BD.
 */
class AuthAttempt extends Model
{
    /** Los intentos más viejos que esto (o que la ventana de login, si es mayor) ya no influyen y se purgan. */
    private const MIN_PURGE_MINUTES = 1440;

    /** Longitud de tb_intentos_auth.correo (igual a tb_usuarios.email). */
    private const EMAIL_MAX_LENGTH = 250;

    protected string $table      = 'tb_intentos_auth';
    protected string $primaryKey = 'id_intento';

    /** Un correo más largo que la columna no puede existir; se recorta igual al registrar y al contar. */
    private static function emailKey(string $correo): string
    {
        return mb_substr(User::normalizeEmail($correo), 0, self::EMAIL_MAX_LENGTH);
    }

    /**
     * Registra un intento y devuelve su id. $userId es null si el correo no existe.
     */
    public function register(string $tipo, string $ip, string $correo, ?int $userId): int
    {
        $purgeMinutes = max(self::MIN_PURGE_MINUTES, AuthSettings::loginWindowMinutes());
        $this->db->exec(
            'DELETE FROM tb_intentos_auth WHERE fyh_creacion < NOW() - INTERVAL ' . $purgeMinutes . ' MINUTE'
        );

        $this->db->prepare(
            'INSERT INTO tb_intentos_auth (tipo, ip, correo, id_usuario) VALUES (?, ?, ?, ?)'
        )->execute([$tipo, $ip, self::emailKey($correo), $userId]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Indica si el intento $idIntento supera el límite por correo o por IP dentro de la ventana
     * configurada (ver AuthSettings), contando solo los intentos con id menor o igual al suyo.
     */
    public function isBlocked(string $tipo, int $idIntento, string $ip, string $correo): bool
    {
        $window = 'fyh_creacion >= NOW() - INTERVAL ' . AuthSettings::loginWindowMinutes() . ' MINUTE';

        $byEmail = $this->query(
            "SELECT COUNT(*) AS total FROM tb_intentos_auth
             WHERE tipo = ? AND correo = ? AND id_intento <= ? AND $window",
            [$tipo, self::emailKey($correo), $idIntento]
        );
        if ((int)$byEmail[0]['total'] > AuthSettings::loginMaxPerEmail()) {
            return true;
        }

        $byIp = $this->query(
            "SELECT COUNT(*) AS total FROM tb_intentos_auth
             WHERE tipo = ? AND ip = ? AND id_intento <= ? AND $window",
            [$tipo, $ip, $idIntento]
        );

        return (int)$byIp[0]['total'] > AuthSettings::loginMaxPerIp();
    }

    /**
     * Cuenta los intentos de una IP en los últimos $seconds segundos.
     */
    public function countSince(string $tipo, string $ip, int $seconds): int
    {
        $rows = $this->query(
            'SELECT COUNT(*) AS total FROM tb_intentos_auth
             WHERE tipo = ? AND ip = ? AND fyh_creacion >= NOW() - INTERVAL ' . (int)$seconds . ' SECOND',
            [$tipo, $ip]
        );

        return (int)$rows[0]['total'];
    }

    /**
     * Cuenta los intentos de un usuario existente en los últimos $seconds segundos,
     * anteriores al intento $beforeId (para no contar el que se está evaluando).
     */
    public function recentForUser(string $tipo, int $userId, int $seconds, int $beforeId): int
    {
        $rows = $this->query(
            'SELECT COUNT(*) AS total FROM tb_intentos_auth
             WHERE tipo = ? AND id_usuario = ? AND id_intento < ?
               AND fyh_creacion >= NOW() - INTERVAL ' . (int)$seconds . ' SECOND',
            [$tipo, $userId, $beforeId]
        );

        return (int)$rows[0]['total'];
    }

    /**
     * Indica si una solicitud de restablecimiento debe ignorarse en silencio: la IP superó el máximo
     * por hora, o el usuario ya tuvo otra solicitud dentro del tiempo de espera.
     * $attemptId es la solicitud que se evalúa; $userId es null si el correo no existe.
     */
    public function resetThrottled(int $attemptId, string $ip, ?int $userId): bool
    {
        if ($this->countSince('reset', $ip, 3600) > AuthSettings::resetMaxPerIpHour()) {
            return true;
        }

        return $userId !== null
            && $this->recentForUser('reset', $userId, AuthSettings::resetCooldownMinutes() * 60, $attemptId) > 0;
    }

    /**
     * Borra los intentos de un correo (tras un login correcto).
     */
    public function clear(string $tipo, string $correo): void
    {
        $this->db->prepare('DELETE FROM tb_intentos_auth WHERE tipo = ? AND correo = ?')
            ->execute([$tipo, self::emailKey($correo)]);
    }
}

<?php

namespace App\Models;

use App\Core\Model;
use App\Helpers\AuthSettings;

/**
 * Sesiones "recordarme", una fila por dispositivo.
 *
 * La cookie lleva `selector:validador`; en BD solo se guarda el hash del validador.
 * La IP se registra pero no se exige que coincida: las IP móviles cambian con frecuencia.
 */
class RememberToken extends Model
{
    protected string $table      = 'tb_sesiones_recordadas';
    protected string $primaryKey = 'id_sesion';

    /**
     * Emite un token para un dispositivo nuevo.
     *
     * @return array{cookie: string, expira: string}
     */
    public function issue(int $userId, string $ip, ?string $userAgent): array
    {
        $this->db->prepare(
            'DELETE FROM tb_sesiones_recordadas WHERE id_usuario = ? AND fyh_expira < NOW()'
        )->execute([$userId]);

        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));

        $this->db->prepare(
            'INSERT INTO tb_sesiones_recordadas (id_usuario, selector, token_hash, fyh_expira, ip, user_agent)
             VALUES (?, ?, ?, NOW() + INTERVAL ' . AuthSettings::rememberDays() . ' DAY, ?, ?)'
        )->execute([$userId, $selector, hash('sha256', $validator), $ip, self::truncateAgent($userAgent)]);

        return ['cookie' => "$selector:$validator", 'expira' => $this->expiryOf($selector)];
    }

    /**
     * Valida un token y rota el validador solo de esa fila.
     * Un token inválido no modifica ninguna fila (una cookie vieja no cierra el dispositivo).
     *
     * @return array{id_usuario: int, ip_changed: bool, cookie: string, expira: string}|null
     */
    public function consume(string $selector, string $validator, string $ip, ?string $userAgent): ?array
    {
        $rows = $this->query(
            'SELECT id_sesion, id_usuario, token_hash, ip FROM tb_sesiones_recordadas
             WHERE selector = ? AND fyh_expira > NOW()',
            [$selector]
        );
        if (!$rows || !hash_equals($rows[0]['token_hash'], hash('sha256', $validator))) {
            return null;
        }

        $session      = $rows[0];
        $newValidator = bin2hex(random_bytes(32));

        // Condicionado al hash anterior: de dos usos simultáneos del mismo token solo uno rota.
        $stmt = $this->db->prepare(
            'UPDATE tb_sesiones_recordadas
             SET token_hash = ?, fyh_expira = NOW() + INTERVAL ' . AuthSettings::rememberDays() . ' DAY,
                 ip = ?, user_agent = ?, fyh_ultimo_uso = NOW()
             WHERE id_sesion = ? AND token_hash = ?'
        );
        $stmt->execute([
            hash('sha256', $newValidator), $ip, self::truncateAgent($userAgent),
            $session['id_sesion'], $session['token_hash'],
        ]);
        if ($stmt->rowCount() === 0) {
            return null;
        }

        return [
            'id_usuario' => (int)$session['id_usuario'],
            'ip_changed' => $session['ip'] !== $ip,
            'cookie'     => "$selector:$newValidator",
            'expira'     => $this->expiryOf($selector),
        ];
    }

    public function revoke(string $selector): void
    {
        $this->db->prepare('DELETE FROM tb_sesiones_recordadas WHERE selector = ?')->execute([$selector]);
    }

    public function revokeAllFor(int $userId): void
    {
        $this->db->prepare('DELETE FROM tb_sesiones_recordadas WHERE id_usuario = ?')->execute([$userId]);
    }

    private function expiryOf(string $selector): string
    {
        $rows = $this->query('SELECT fyh_expira FROM tb_sesiones_recordadas WHERE selector = ?', [$selector]);

        return (string)$rows[0]['fyh_expira'];
    }

    private static function truncateAgent(?string $userAgent): ?string
    {
        return $userAgent === null ? null : mb_substr($userAgent, 0, 255);
    }
}

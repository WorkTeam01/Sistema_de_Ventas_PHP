<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class ActivityLog extends Model
{
    protected string $table = 'tb_activity_log';
    protected string $primaryKey = 'id_log';

    /**
     * Registra una operación sensible en el log de auditoría.
     * Nunca lanza excepciones: si el log falla, la operación principal no se interrumpe.
     *
     * @param string     $action     Verbo de la operación: 'delete', 'price_change', 'role_change', 'update'
     * @param string     $entity     Entidad afectada: 'sale', 'purchase', 'product', 'user', 'client', 'supplier'
     * @param int|null   $entityId   PK del registro afectado
     * @param string     $description Texto legible para la vista de auditoría
     * @param array|null $before     Estado anterior del registro (excluir campos sensibles)
     * @param array|null $after      Estado nuevo del registro
     */
    public static function record(
        string $action,
        string $entity,
        ?int $entityId,
        string $description = '',
        ?array $before = null,
        ?array $after = null
    ): void {
        try {
            $user = Auth::user();
            $log = new self();
            $log->create([
                'id_usuario'       => $user['id_usuario'] ?? null,
                'usuario_nombre'   => trim(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')),
                'accion'           => $action,
                'entidad'          => $entity,
                'entidad_id'       => $entityId,
                'descripcion'      => mb_substr($description, 0, 255),
                'datos_anteriores' => $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                'datos_nuevos'     => $after  !== null ? json_encode($after,  JSON_UNESCAPED_UNICODE) : null,
                'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('[ActivityLog] fallo al registrar: ' . $e->getMessage());
        }
    }
}

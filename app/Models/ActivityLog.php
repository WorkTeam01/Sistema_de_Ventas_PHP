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
     * @param string     $action      Verbo: 'delete', 'price_change', 'role_change', 'update', 'stock_adjustment'
     * @param string     $entity      Entidad: 'sale', 'purchase', 'product', 'user', 'client', 'supplier'
     * @param int|null   $entityId    PK del registro afectado
     * @param string     $description Texto legible para la vista de auditoría
     * @param array|null $before      Estado anterior (excluir campos sensibles)
     * @param array|null $after       Estado nuevo
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

    /**
     * Consulta registros de auditoría acotados por rango de fechas (obligatorio).
     * Máximo 90 días por consulta para evitar full scans.
     *
     * @param string $from    Fecha inicio 'Y-m-d'
     * @param string $to      Fecha fin 'Y-m-d'
     * @param array  $filters Claves opcionales: entity, action, id_usuario
     */
    public function search(string $from, string $to, array $filters = []): array
    {
        $params = [
            $from . ' 00:00:00',
            $to   . ' 23:59:59',
        ];

        $where = 'WHERE l.fyh_creacion BETWEEN ? AND ?';

        if (!empty($filters['entity'])) {
            $where   .= ' AND l.entidad = ?';
            $params[] = $filters['entity'];
        }
        if (!empty($filters['action'])) {
            $where   .= ' AND l.accion = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['id_usuario'])) {
            $where   .= ' AND l.id_usuario = ?';
            $params[] = (int)$filters['id_usuario'];
        }

        return $this->query(
            "SELECT l.*,
                    COALESCE(u.nombres, l.usuario_nombre) AS nombre_display
             FROM tb_activity_log l
             LEFT JOIN tb_usuarios u ON l.id_usuario = u.id_usuario
             $where
             ORDER BY l.id_log DESC",
            $params
        );
    }

    /** Lista de entidades distintas registradas — para poblar el <select> de filtro. */
    public function availableEntities(): array
    {
        return $this->query(
            "SELECT DISTINCT entidad FROM tb_activity_log ORDER BY entidad"
        );
    }

    /** Lista de acciones distintas registradas — para poblar el <select> de filtro. */
    public function availableActions(): array
    {
        return $this->query(
            "SELECT DISTINCT accion FROM tb_activity_log ORDER BY accion"
        );
    }

    /** Elimina registros más antiguos que $days días. Devuelve filas eliminadas. */
    public function purgeOlderThan(int $days): int
    {
        $stmt = $this->db->prepare(
            "DELETE FROM tb_activity_log WHERE fyh_creacion < NOW() - INTERVAL ? DAY"
        );
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }
}

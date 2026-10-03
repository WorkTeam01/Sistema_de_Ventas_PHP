<?php

namespace App\Models;

use App\Core\Model;

/**
 * Reserva del número de venta del POS: el id autoincremental de tb_pos_reservas ES el nro_venta,
 * así dos vendedores nunca reciben el mismo número ni comparten carrito.
 */
class PosReservation extends Model
{
    protected string $table      = 'tb_pos_reservas';
    protected string $primaryKey = 'id_reserva';

    /**
     * Devuelve el nro_venta de la reserva abierta del usuario; la crea si no tiene.
     * Si hay más de una abierta (datos previos), conserva la más reciente y libera las demás
     * junto con sus líneas de carrito sin venta, sin tocar nada de otros usuarios.
     */
    public function openFor(int $userId): int
    {
        $open = $this->query(
            'SELECT id_reserva FROM tb_pos_reservas
             WHERE id_usuario = ? AND fyh_liberacion IS NULL
             ORDER BY id_reserva DESC',
            [$userId]
        );

        if ($open === []) {
            $this->db->prepare('INSERT INTO tb_pos_reservas (id_usuario) VALUES (?)')->execute([$userId]);

            return (int)$this->db->lastInsertId();
        }

        $keep = (int)array_shift($open)['id_reserva'];

        if ($open !== []) {
            $this->releaseDuplicates(array_column($open, 'id_reserva'));
        }

        return $keep;
    }

    /**
     * Con $forUpdate bloquea la fila hasta el fin de la transacción en curso, para que dos cobros
     * simultáneos de la misma reserva se serialicen (SQLite no admite FOR UPDATE: su transacción ya serializa).
     */
    public function isOpenFor(int $nroVenta, int $userId, bool $forUpdate = false): bool
    {
        $lock = $forUpdate && $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'sqlite' ? ' FOR UPDATE' : '';
        $rows = $this->query(
            'SELECT 1 FROM tb_pos_reservas
             WHERE id_reserva = ? AND id_usuario = ? AND fyh_liberacion IS NULL' . $lock,
            [$nroVenta, $userId]
        );

        return $rows !== [];
    }

    /**
     * Marca la reserva como liberada (la fila nunca se borra; los huecos de numeración son aceptables).
     * No abre transacción: se llama dentro de la del cobro o de la cancelación.
     */
    public function release(int $nroVenta): void
    {
        $this->db->prepare(
            'UPDATE tb_pos_reservas SET fyh_liberacion = CURRENT_TIMESTAMP
             WHERE id_reserva = ? AND fyh_liberacion IS NULL'
        )->execute([$nroVenta]);
    }

    private function releaseDuplicates(array $ids): void
    {
        $this->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $this->release((int)$id);
                $this->db->prepare(
                    'DELETE FROM tb_carrito
                     WHERE nro_venta = ? AND nro_venta NOT IN (SELECT nro_venta FROM tb_ventas)'
                )->execute([(int)$id]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

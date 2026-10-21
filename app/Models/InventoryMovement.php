<?php

namespace App\Models;

use App\Core\Model;

class InventoryMovement extends Model
{
    protected string $table = 'tb_movimientos_inventario';
    protected string $primaryKey = 'id_movimiento';

    public function record(
        int $productId,
        int $quantity,
        string $type,
        ?string $reference,
        ?int $userId,
        string $userName,
        ?string $date = null
    ): void {
        if ($quantity === 0) {
            return;
        }

        $product = $this->query(
            'SELECT codigo, nombre, unidad_medida FROM tb_almacen WHERE id_producto = ?',
            [$productId]
        )[0] ?? null;
        if ($product === null) {
            throw new \RuntimeException('No se puede registrar un movimiento de un producto inexistente.');
        }
        if ($userName === '' && $userId !== null) {
            $user = $this->query('SELECT nombres FROM tb_usuarios WHERE id_usuario = ?', [$userId])[0] ?? null;
            $userName = (string)($user['nombres'] ?? 'Usuario eliminado');
        }

        $this->db->prepare(
            'INSERT INTO tb_movimientos_inventario
                (id_producto, codigo_producto, nombre_producto, unidad_medida, fecha,
                 cantidad, tipo, referencia, id_usuario, usuario_nombre)
             VALUES (?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP), ?, ?, ?, ?, ?)'
        )->execute([
            $productId,
            $product['codigo'],
            $product['nombre'],
            $product['unidad_medida'],
            $date,
            $quantity,
            mb_substr($type, 0, 50),
            $reference !== null ? mb_substr($reference, 0, 255) : null,
            $userId,
            mb_substr($userName !== '' ? $userName : 'Usuario eliminado', 0, 250),
        ]);
    }
}

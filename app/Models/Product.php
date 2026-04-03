<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para la tabla tb_almacen.
 *
 * Hereda all(), find(), create(), update(), delete() y count() de Model.
 */
class Product extends Model
{
    protected string $table      = 'tb_almacen';
    protected string $primaryKey = 'id_producto';

    /**
     * Retorna todos los productos con nombre de categoría y email del usuario registrador.
     *
     * @return array Lista de productos con datos relacionados.
     */
    public function allWithCategories(): array
    {
        return $this->query(
            "SELECT al.*, ca.nombre_categoria, us.email AS email_usuario
             FROM tb_almacen al
             INNER JOIN tb_categorias ca ON al.id_categoria = ca.id_categoria
             INNER JOIN tb_usuarios us ON al.id_usuario = us.id_usuario
             ORDER BY al.id_producto DESC"
        );
    }

    /**
     * Genera el próximo código de producto en formato P-00001.
     *
     * @return string Código generado.
     */
    public function nextCode(): string
    {
        $next = $this->count() + 1;
        return 'P-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Indica si el producto está referenciado en tb_carrito o tb_compras.
     *
     * @param int|string $id ID del producto.
     * @return bool true si existe al menos una referencia activa.
     */
    public function isReferenced(int|string $id): bool
    {
        $inCart     = $this->query("SELECT COUNT(*) AS total FROM tb_carrito WHERE id_producto = ?", [$id]);
        $inPurchase = $this->query("SELECT COUNT(*) AS total FROM tb_compras WHERE id_producto = ?", [$id]);
        return ($inCart[0]['total'] ?? 0) > 0 || ($inPurchase[0]['total'] ?? 0) > 0;
    }

    /**
     * Devuelve un desglose de cuántos registros tiene el producto en cada tabla referenciada.
     *
     * @param int $id ID del producto.
     * @return array{carrito: int, compras: int}
     */
    public function getReferenceCount(int $id): array
    {
        $carrito = $this->query(
            "SELECT COUNT(*) AS total FROM tb_carrito WHERE id_producto = ?",
            [$id]
        );
        $compras = $this->query(
            "SELECT COUNT(*) AS total FROM tb_compras WHERE id_producto = ?",
            [$id]
        );

        return [
            'carrito' => (int) ($carrito[0]['total'] ?? 0),
            'compras' => (int) ($compras[0]['total'] ?? 0),
        ];
    }
}

<?php

namespace App\Helpers;

class ActivityLogRenderer
{
    private static array $labels = [
        // users
        'id_usuario'       => 'ID Usuario',
        'nombres'          => 'Nombres',
        'apellidos'        => 'Apellidos',
        'email'            => 'Correo electrónico',
        'id_rol'           => 'ID Rol',
        'nombre_rol'       => 'Rol',
        // products
        'nombre'           => 'Nombre',
        'precio_venta'     => 'Precio de venta',
        'precio_compra'    => 'Precio de compra',
        'stock'            => 'Stock',
        'descripcion'      => 'Descripción',
        // clients / suppliers
        'nombre_cliente'   => 'Cliente',
        'nit_ci_cliente'   => 'NIT/CI',
        'email_cliente'    => 'Correo (cliente)',
        'nombre_proveedor' => 'Proveedor',
        'empresa'          => 'Empresa',
        // sales / purchases
        'nro_venta'        => 'Nro. Venta',
        'nro_compra'       => 'Nro. Compra',
        'total_pagado'     => 'Total pagado',
        'fyh_creacion'     => 'Fecha/Hora',
        'items'            => 'Ítems',
        // sale/purchase item fields
        'nombre_producto'  => 'Producto',
        'cantidad'         => 'Cantidad',
        'precio_unitario'  => 'Precio unitario',
        'subtotal'         => 'Subtotal',
    ];

    public static function label(string $key): string
    {
        return self::$labels[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Clase de badge Bootstrap asociada a una acción del log.
     */
    public static function badgeClass(string $accion): string
    {
        return match ($accion) {
            'delete'       => 'badge-danger',
            'price_change' => 'badge-warning',
            'role_change'  => 'badge-info',
            default        => 'badge-primary',
        };
    }

    /**
     * Decode a JSON string and return view-ready rows.
     *
     * Each row has:
     *   'label' => human-readable field name
     *   'value' => scalar value or array of item arrays
     *   'type'  => 'scalar' | 'list'
     *
     * Returns empty array when json is null or invalid.
     *
     * @return array<int, array{label: string, value: mixed, type: string}>
     */
    public static function prepare(?string $json): array
    {
        if ($json === null) {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $rows = [];
        foreach ($decoded as $key => $value) {
            $isList = is_array($value) && isset($value[0]) && is_array($value[0]);
            $rows[] = [
                'label' => self::label((string)$key),
                'value' => $value,
                'type'  => $isList ? 'list' : 'scalar',
            ];
        }

        return $rows;
    }
}

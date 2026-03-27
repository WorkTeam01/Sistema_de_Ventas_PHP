<?php

namespace App\Core;

/**
 * Contrato que deben implementar todos los middlewares del sistema.
 */
interface Middleware
{
    /**
     * Ejecuta la lógica de acceso del middleware.
     * Devuelve true si la petición puede continuar,
     * o redirige y termina la ejecución si no cumple la condición.
     *
     * @return bool True si el acceso está permitido.
     */
    public function handle(): bool;
}

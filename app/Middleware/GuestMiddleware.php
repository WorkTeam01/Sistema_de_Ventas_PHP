<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Middleware;

/**
 * Middleware que restringe el acceso a usuarios sin sesión activa (invitados).
 * Redirige al dashboard si el usuario ya está autenticado.
 */
class GuestMiddleware implements Middleware
{
    /**
     * Permite el acceso si no hay sesión activa; redirige al dashboard si ya la hay.
     *
     * @return bool True si el usuario no está autenticado.
     */
    public function handle(): bool
    {
        if (!Auth::check()) {
            return true;
        }

        header('Location: ' . rtrim(Config::get('APP_URL', ''), '/'));
        exit();
    }
}

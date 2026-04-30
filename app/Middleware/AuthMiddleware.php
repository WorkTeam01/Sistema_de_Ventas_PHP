<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Middleware;

/**
 * Middleware que protege rutas que requieren sesión activa.
 * Redirige a /auth con mensaje flash si el usuario no está autenticado.
 */
class AuthMiddleware implements Middleware
{
    /**
     * Permite el acceso si hay sesión activa; redirige a /auth si no la hay.
     *
     * @return bool True si el usuario está autenticado.
     */
    public function handle(): bool
    {
        if (Auth::check()) {
            return true;
        }

        if (Auth::loginWithCookie()) {
            return true;
        }

        Auth::startSession();
        $_SESSION['mensaje'] = 'Debes iniciar sesión para acceder a esta página.';
        $_SESSION['icono'] = 'warning';

        header('Location: ' . rtrim(Config::get('APP_URL', ''), '/') . '/auth');
        exit();
    }
}

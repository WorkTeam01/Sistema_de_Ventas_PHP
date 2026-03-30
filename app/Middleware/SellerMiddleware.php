<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Middleware;

/**
 * Middleware que restringe el acceso a usuarios con rol Administrador o Vendedor.
 * Redirige a /auth si no hay sesión, o a la página de error si no tiene permisos.
 */
class SellerMiddleware implements Middleware
{
    /**
     * Permite el acceso si el usuario tiene rol 'Administrador' o 'Vendedor'; redirige si no.
     *
     * @return bool True si el usuario es Administrador o Vendedor.
     */
    public function handle(): bool
    {
        if (!Auth::check()) {
            Auth::startSession();
            $_SESSION['mensaje'] = 'Debes iniciar sesión para acceder a esta página.';
            $_SESSION['icono'] = 'warning';
            header('Location: ' . rtrim(Config::get('APP_URL', ''), '/') . '/auth');
            exit();
        }

        $role = Auth::role();
        if ($role === 'Administrador' || $role === 'Vendedor') {
            return true;
        }

        Auth::startSession();
        $_SESSION['mensaje'] = 'No tienes permisos para acceder a esta página.';
        $_SESSION['icono'] = 'error';
        http_response_code(403);
        header('Location: ' . rtrim(Config::get('APP_URL', ''), '/') . '/errors/403');
        exit();
    }
}

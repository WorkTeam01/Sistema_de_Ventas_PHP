<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Middleware;

/**
 * Middleware que restringe el acceso a usuarios con rol Administrador.
 * Redirige a /auth si no hay sesión, o a la página de error si no tiene permisos.
 */
class AdminMiddleware implements Middleware
{
    /**
     * Permite el acceso si el usuario tiene rol 'Administrador'; redirige si no.
     *
     * @return bool True si el usuario es Administrador.
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

        if (Auth::role() === 'Administrador') {
            return true;
        }

        Auth::startSession();
        $_SESSION['mensaje'] = 'No tienes permisos para acceder a esta página.';
        $_SESSION['icono'] = 'error';
        header('Location: ' . rtrim(Config::get('APP_URL', ''), '/') . '/error/error.php');
        exit();
    }
}

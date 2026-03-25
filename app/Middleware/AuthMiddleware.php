<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Middleware;

class AuthMiddleware implements Middleware
{
    public function handle(): bool
    {
        if (Auth::check()) {
            return true;
        }

        Auth::startSession();
        $_SESSION['mensaje'] = 'Debes iniciar sesión para acceder a esta página.';
        $_SESSION['icono'] = 'warning';

        header('Location: ' . rtrim(Config::get('APP_URL', ''), '/') . '/auth');
        exit();
    }
}

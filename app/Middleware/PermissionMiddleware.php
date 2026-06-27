<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Middleware;

class PermissionMiddleware implements Middleware
{
    public function __construct(private string $permiso) {}

    public function handle(): bool
    {
        if (!Auth::check()) {
            Auth::startSession();
            $_SESSION['mensaje'] = 'Debes iniciar sesión para acceder a esta página.';
            $_SESSION['icono']   = 'warning';
            header('Location: ' . rtrim(Config::get('APP_URL', ''), '/') . '/auth');
            exit();
        }

        if (Auth::can($this->permiso)) {
            return true;
        }

        Auth::startSession();
        $_SESSION['mensaje'] = 'No tienes permisos para acceder a esta página.';
        $_SESSION['icono']   = 'error';
        http_response_code(403);
        header('Location: ' . rtrim(Config::get('APP_URL', ''), '/') . '/errors/403');
        exit();
    }
}

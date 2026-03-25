<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Middleware;

class GuestMiddleware implements Middleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            return true;
        }

        header('Location: ' . rtrim(Config::get('APP_URL', ''), '/'));
        exit();
    }
}

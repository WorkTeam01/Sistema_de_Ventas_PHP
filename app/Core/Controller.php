<?php

namespace App\Core;

class Controller
{
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../../' . ltrim($viewPath, '/');
    }

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit();
    }
}

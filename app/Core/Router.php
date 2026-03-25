<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->routes['GET'][$path] = $handler;
        $this->middlewares['GET'][$path] = $middleware;
    }

    public function post(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->routes['POST'][$path] = $handler;
        $this->middlewares['POST'][$path] = $middleware;
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = '/' . trim($uri, '/');
        if ($uri === '//') {
            $uri = '/';
        }

        $methodRoutes = $this->routes[$method] ?? [];

        foreach ($methodRoutes as $route => $handler) {
            $normalizedRoute = rtrim($route, '/');
            if ($normalizedRoute === '') {
                $normalizedRoute = '/';
            }

            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $normalizedRoute);
            $normalizedPattern = $pattern === '/' ? '/' : rtrim($pattern, '/');
            $pattern = '#^' . $normalizedPattern . '$#';

            $normalizedUri = rtrim($uri, '/');
            if ($normalizedUri === '') {
                $normalizedUri = '/';
            }

            if (!preg_match($pattern, $normalizedUri, $matches)) {
                continue;
            }

            array_shift($matches);

            foreach (($this->middlewares[$method][$route] ?? []) as $middlewareKey) {
                $this->executeMiddleware($middlewareKey);
            }

            $this->executeHandler($handler, $matches);
            return;
        }

        http_response_code(404);
        require_once __DIR__ . '/../../error/error.php';
    }

    private function executeHandler(callable|array|string $handler, array $params = []): void
    {
        if (is_callable($handler) && !is_string($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = new $class();
            if (property_exists($controller, 'action')) {
                $controller->action = $action;
            }
            call_user_func_array([$controller, $action], $params);
            return;
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $action] = explode('@', $handler, 2);
            $controller = new $class();
            if (property_exists($controller, 'action')) {
                $controller->action = $action;
            }
            call_user_func_array([$controller, $action], $params);
            return;
        }

        throw new \RuntimeException('Handler de ruta inválido.');
    }

    private function executeMiddleware(string $key): void
    {
        $map = [
            'auth' => \App\Middleware\AuthMiddleware::class,
            'guest' => \App\Middleware\GuestMiddleware::class,
            'admin' => \App\Middleware\AdminMiddleware::class,
        ];

        if (!isset($map[$key])) {
            throw new \RuntimeException("Middleware '{$key}' no registrado.");
        }

        $middleware = new $map[$key]();
        $middleware->handle();
    }
}

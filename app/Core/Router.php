<?php

namespace App\Core;

/**
 * Enrutador HTTP simple que resuelve rutas GET/POST con soporte de parámetros dinámicos y middleware.
 */
class Router
{
    /** Rutas registradas indexadas por método HTTP y path. */
    private array $routes = [];
    /** Middleware asociado a cada ruta, indexado igual que $routes. */
    private array $middlewares = [];

    /**
     * Registra una ruta GET.
     *
     * @param string                    $path       Patrón de ruta (ej. '/users/{id}').
     * @param callable|array|string     $handler    Handler: closure, [Class, 'method'] o 'Class@method'.
     * @param array                     $middleware Claves de middleware a ejecutar ('auth', 'guest', 'admin').
     */
    public function get(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->routes['GET'][$path] = $handler;
        $this->middlewares['GET'][$path] = $middleware;
    }

    /**
     * Registra una ruta POST.
     *
     * @param string                $path       Patrón de ruta.
     * @param callable|array|string $handler    Handler de la ruta.
     * @param array                 $middleware Claves de middleware a ejecutar.
     */
    public function post(string $path, callable|array|string $handler, array $middleware = []): void
    {
        $this->routes['POST'][$path] = $handler;
        $this->middlewares['POST'][$path] = $middleware;
    }

    /**
     * Despacha la petición: resuelve la URI contra las rutas registradas,
     * ejecuta el middleware y luego el handler. Devuelve 404 si no hay coincidencia.
     *
     * @param string $method Método HTTP ('GET', 'POST').
     * @param string $uri    URI de la petición (sin query string).
     */
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
        require_once __DIR__ . '/../../views/errors/404.php';
    }

    /**
     * Invoca el handler de ruta con los parámetros capturados.
     * Soporta closure, [Class, 'method'] y 'Class@method'.
     *
     * @param callable|array|string $handler Handler a ejecutar.
     * @param array                 $params  Parámetros de ruta capturados.
     */
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

    /**
     * Resuelve la clase de middleware por su clave e invoca handle().
     * Lanza RuntimeException si la clave no está registrada.
     *
     * @param string $key Clave del middleware ('auth', 'guest', 'admin').
     */
    private function executeMiddleware(string $key): void
    {
        if (str_starts_with($key, 'can:')) {
            $permiso = substr($key, 4);
            (new \App\Middleware\PermissionMiddleware($permiso))->handle();
            return;
        }

        $map = [
            'auth'   => \App\Middleware\AuthMiddleware::class,
            'guest'  => \App\Middleware\GuestMiddleware::class,
            'admin'  => \App\Middleware\AdminMiddleware::class,
            'seller' => \App\Middleware\SellerMiddleware::class,
        ];

        if (!isset($map[$key])) {
            throw new \RuntimeException("Middleware '{$key}' no registrado.");
        }

        $middleware = new $map[$key]();
        $middleware->handle();
    }
}

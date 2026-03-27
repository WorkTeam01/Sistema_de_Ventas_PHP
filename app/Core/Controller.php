<?php

namespace App\Core;

use App\Core\Auth;
use App\Core\Database;

/**
 * Clase base para todos los controladores MVC.
 *
 * Provee métodos de renderizado, redirección, validación CSRF,
 * mensajes flash y acceso a datos de sesión.
 */
class Controller
{
    /** Nombre de la acción actualmente despachada (asignado por el Router). */
    public string $action = '';

    /**
     * Renderiza una vista sin layout, extrayendo $data como variables locales.
     *
     * @param string $viewPath Ruta relativa a la raíz del proyecto.
     * @param array  $data     Variables a inyectar en la vista.
     */
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);

        if (!isset($Año)) {
            $Año = date('Y');
        }

        require_once __DIR__ . '/../../' . ltrim($viewPath, '/');
    }

    /**
     * Renderiza una vista envuelta en el layout completo (parte1 + mensajes + parte2).
     *
     * @param string $viewPath     Ruta relativa a la raíz del proyecto.
     * @param array  $data         Variables a inyectar en la vista.
     * @param bool   $withMessages Si es true incluye el bloque de mensajes flash.
     */
    protected function renderWithLayout(string $viewPath, array $data = [], bool $withMessages = true): void
    {
        extract($data);

        if (!isset($Año)) {
            $Año = date('Y');
        }

        require __DIR__ . '/../../views/layout/parte1.php';
        require __DIR__ . '/../../' . ltrim($viewPath, '/');

        if ($withMessages) {
            require __DIR__ . '/../../views/layout/mensajes.php';
        }

        require __DIR__ . '/../../views/layout/parte2.php';
    }

    /**
     * Emite un header Location y termina la ejecución.
     *
     * @param string $url URL de destino.
     */
    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit();
    }

    /**
     * Devuelve una respuesta JSON con el código HTTP indicado y termina la ejecución.
     *
     * @param mixed $data   Datos a serializar.
     * @param int   $status Código HTTP de respuesta.
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }

    /**
     * Lee y sanitiza input de GET y POST.
     * Si $key es null devuelve todos los campos sanitizados.
     *
     * @param string|null $key     Nombre del campo a leer.
     * @param mixed       $default Valor por defecto si el campo no existe.
     * @return mixed Campo sanitizado o $default.
     */
    protected function input(?string $key = null, mixed $default = null): mixed
    {
        $data = array_merge($_GET ?? [], $_POST ?? []);

        if ($key === null) {
            return $this->sanitize($data);
        }

        return isset($data[$key]) ? $this->sanitize($data[$key]) : $default;
    }

    /**
     * Aplica htmlspecialchars(trim()) recursivamente a strings.
     * Arrays son procesados elemento a elemento; otros tipos se devuelven sin cambios.
     *
     * @param mixed $data Dato o array a sanitizar.
     * @return mixed Dato sanitizado.
     */
    protected function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->sanitize($v);
            }
            return $data;
        }

        if (!is_string($data)) {
            return $data;
        }

        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida un array de datos contra reglas declarativas.
     * Reglas soportadas: required, email, min:N (separadas por |).
     *
     * @param array $data  Datos a validar (campo => valor).
     * @param array $rules Reglas por campo (campo => 'required|email|min:6').
     * @return true|array  True si todo es válido; array de errores si no.
     */
    protected function validate(array $data, array $rules): true|array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? '';
            $ruleList = explode('|', $ruleString);

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && ($value === '' || $value === null)) {
                    $errors[$field] = "El campo {$field} es obligatorio.";
                    continue;
                }

                if ($rule === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "El formato del campo {$field} no es válido.";
                    continue;
                }

                if (str_starts_with($rule, 'min:') && $value !== '') {
                    $min = (int) substr($rule, 4);
                    if (mb_strlen((string) $value) < $min) {
                        $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres.";
                    }
                }
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Devuelve un array con los datos de sesión y conexión PDO para inyectar en las vistas:
     * URL, pdo, id_usuario_sesion, nombres_sesion, rol_sesion.
     *
     * @return array Datos de sesión listos para pasar a renderWithLayout().
     */
    protected function sessionData(): array
    {
        Auth::startSession();
        $usuario = Auth::user() ?? [];
        return [
            'URL'               => BASE_URL,
            'pdo'               => Database::getInstance()->getConnection(),
            'id_usuario_sesion' => (int) ($usuario['id_usuario'] ?? 0),
            'nombres_sesion'    => $usuario['nombres'] ?? '',
            'rol_sesion'        => $usuario['rol'] ?? '',
        ];
    }

    /**
     * Guarda un mensaje flash y su tipo de icono en sesión para mostrarse en la siguiente petición.
     *
     * @param string $mensaje Texto del mensaje.
     * @param string $icono   Tipo de icono SweetAlert2 ('success', 'error', 'warning', etc.).
     */
    protected function flash(string $mensaje, string $icono = 'success'): void
    {
        Auth::startSession();
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['icono']   = $icono;
    }

    /**
     * Valida el token CSRF recibido en $_POST['csrf_token'].
     * Termina la ejecución con un mensaje de error si el token es inválido.
     */
    protected function validateCsrfOrFail(): void
    {
        if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Error de seguridad: Token CSRF inválido.');
        }
    }
}

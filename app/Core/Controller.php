<?php

namespace App\Core;

class Controller
{
    public string $action = '';

    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);

        if (!isset($Año)) {
            $Año = date('Y');
        }

        require_once __DIR__ . '/../../' . ltrim($viewPath, '/');
    }

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

    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit();
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }

    protected function input(?string $key = null, mixed $default = null): mixed
    {
        $data = array_merge($_GET ?? [], $_POST ?? []);

        if ($key === null) {
            return $this->sanitize($data);
        }

        return isset($data[$key]) ? $this->sanitize($data[$key]) : $default;
    }

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
}

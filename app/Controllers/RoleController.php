<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Role;

class RoleController extends Controller
{
    private function sessionData(): array
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

    private function flash(string $mensaje, string $icono = 'success'): void
    {
        Auth::startSession();
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['icono']   = $icono;
    }

    private function validateCsrfOrFail(): void
    {
        if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Error de seguridad: Token CSRF inválido.');
        }
    }

    public function index(): void
    {
        $roleModel   = new Role();
        $roles_datos = $roleModel->all();

        $this->renderWithLayout('views/roles/index.php', array_merge(
            $this->sessionData(),
            ['roles_datos' => $roles_datos]
        ));
    }

    public function create(): void
    {
        $this->renderWithLayout('views/roles/create.php', array_merge(
            $this->sessionData(),
            ['csrf_token' => Auth::generateCsrfToken()]
        ));
    }

    public function store(): void
    {
        $this->validateCsrfOrFail();

        $rol = trim($_POST['rol'] ?? '');

        if ($rol === '') {
            $this->flash('El nombre del rol es obligatorio.', 'error');
            $this->redirect(BASE_URL . '/roles/create');
        }

        $roleModel = new Role();

        if ($roleModel->create(['rol' => $rol])) {
            $this->flash('El rol se registró exitosamente', 'success');
            $this->redirect(BASE_URL . '/roles');
        }

        $this->flash('Error al crear el rol.', 'error');
        $this->redirect(BASE_URL . '/roles/create');
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('Rol inválido.', 'error');
            $this->redirect(BASE_URL . '/roles');
        }

        $roleModel = new Role();
        $role      = $roleModel->find($id);

        if (!$role) {
            $this->flash('No se encontró el rol solicitado.', 'error');
            $this->redirect(BASE_URL . '/roles');
        }

        $this->renderWithLayout('views/roles/edit.php', array_merge(
            $this->sessionData(),
            [
                'id_rol'     => (int) $role['id_rol'],
                'rol'        => $role['rol'],
                'csrf_token' => Auth::generateCsrfToken(),
            ]
        ));
    }

    public function update(): void
    {
        $this->validateCsrfOrFail();

        $id_rol = (int) ($_POST['id_rol'] ?? 0);
        $rol    = trim($_POST['rol'] ?? '');

        if ($id_rol <= 0 || $rol === '') {
            $this->flash('Datos inválidos para actualizar el rol.', 'error');
            $this->redirect(BASE_URL . '/roles');
        }

        $roleModel = new Role();

        if ($roleModel->update($id_rol, ['rol' => $rol])) {
            $this->flash('El rol se actualizó exitosamente', 'success');
            $this->redirect(BASE_URL . '/roles');
        }

        $this->flash('Error al actualizar el rol.', 'error');
        $this->redirect(BASE_URL . '/roles/edit/' . $id_rol);
    }
}

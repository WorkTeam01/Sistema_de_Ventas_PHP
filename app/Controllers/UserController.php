<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\User;

class UserController extends Controller
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
        $userModel     = new User();
        $usuarios_datos = $userModel->findAllWithRole();

        $this->renderWithLayout('views/users/index.php', array_merge(
            $this->sessionData(),
            ['usuarios_datos' => $usuarios_datos]
        ));
    }

    public function create(): void
    {
        $userModel   = new User();
        $roles_datos = $userModel->findAllRoles();

        $this->renderWithLayout('views/users/create.php', array_merge(
            $this->sessionData(),
            [
                'roles_datos' => $roles_datos,
                'csrf_token'  => Auth::generateCsrfToken(),
            ]
        ));
    }

    public function store(): void
    {
        $this->validateCsrfOrFail();

        $nombres         = trim($_POST['nombres'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $rol             = (int) ($_POST['rol'] ?? 0);
        $password_user   = $_POST['password_user'] ?? '';
        $password_repeat = $_POST['password_repeat'] ?? '';

        if ($nombres === '' || $email === '' || $rol <= 0 || $password_user === '' || $password_repeat === '') {
            $this->flash('Todos los campos son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/users/create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('El formato del correo electrónico es inválido.', 'error');
            $this->redirect(BASE_URL . '/users/create');
        }

        if ($password_user !== $password_repeat) {
            $this->flash('Las contraseñas no coinciden', 'error');
            $this->redirect(BASE_URL . '/users/create');
        }

        $userModel = new User();
        if ($userModel->emailExists($email)) {
            $this->flash('El correo electrónico ya está registrado.', 'error');
            $this->redirect(BASE_URL . '/users/create');
        }

        $passwordHash = password_hash($password_user, PASSWORD_DEFAULT);

        if ($userModel->createUser($nombres, $email, $rol, $passwordHash)) {
            $this->flash('El usuario se registró exitosamente', 'success');
            $this->redirect(BASE_URL . '/users');
        }

        $this->flash('No se pudo registrar el usuario.', 'error');
        $this->redirect(BASE_URL . '/users/create');
    }

    public function show(?int $id = null): void
    {
        $id = $id ?? (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $userModel = new User();
        $usuario   = $userModel->findWithRoleById($id);

        if (!$usuario) {
            $this->flash('No se encontró el usuario solicitado.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $this->renderWithLayout('views/users/show.php', array_merge(
            $this->sessionData(),
            [
                'id_usuario' => (int) $usuario['id_usuario'],
                'nombres'    => $usuario['nombres'],
                'email'      => $usuario['email'],
                'rol'        => $usuario['rol'],
            ]
        ));
    }

    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $userModel = new User();
        $usuario   = $userModel->findWithRoleById($id);

        if (!$usuario) {
            $this->flash('No se encontró el usuario solicitado.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $roles_datos = $userModel->findAllRoles();

        $this->renderWithLayout('views/users/edit.php', array_merge(
            $this->sessionData(),
            [
                'id_usuario'  => (int) $usuario['id_usuario'],
                'nombres'     => $usuario['nombres'],
                'email'       => $usuario['email'],
                'idRolActual' => (int) $usuario['id_rol'],
                'rolActual'   => $usuario['rol'],
                'roles_datos' => $roles_datos,
                'csrf_token'  => Auth::generateCsrfToken(),
            ]
        ));
    }

    public function update(): void
    {
        $this->validateCsrfOrFail();

        $id_usuario      = (int) ($_POST['id_usuario'] ?? 0);
        $nombres         = trim($_POST['nombres'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $rol             = (int) ($_POST['rol'] ?? 0);
        $password_user   = $_POST['password_user'] ?? '';
        $password_repeat = $_POST['password_repeat'] ?? '';

        if ($id_usuario <= 0 || $nombres === '' || $email === '' || $rol <= 0) {
            $this->flash('Datos inválidos para actualizar el usuario.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('El formato del correo electrónico es inválido.', 'error');
            $this->redirect(BASE_URL . '/users/edit/' . $id_usuario);
        }

        $userModel = new User();
        if ($userModel->emailExists($email, $id_usuario)) {
            $this->flash('El correo electrónico ya está registrado por otro usuario.', 'error');
            $this->redirect(BASE_URL . '/users/edit/' . $id_usuario);
        }

        $passwordHash = null;
        if ($password_user !== '') {
            if ($password_user !== $password_repeat) {
                $this->flash('Las contraseñas no coinciden', 'error');
                $this->redirect(BASE_URL . '/users/edit/' . $id_usuario);
            }
            $passwordHash = password_hash($password_user, PASSWORD_DEFAULT);
        }

        if ($userModel->updateUser($id_usuario, $nombres, $email, $rol, $passwordHash)) {
            $this->flash('El usuario se actualizó exitosamente', 'success');
            $this->redirect(BASE_URL . '/users');
        }

        $this->flash('No se pudo actualizar el usuario.', 'error');
        $this->redirect(BASE_URL . '/users/edit/' . $id_usuario);
    }

    public function delete(?int $id = null): void
    {
        $id = $id ?? (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $userModel = new User();
        $usuario   = $userModel->findWithRoleById($id);

        if (!$usuario) {
            $this->flash('No se encontró el usuario solicitado.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $this->renderWithLayout('views/users/delete.php', array_merge(
            $this->sessionData(),
            [
                'id_usuario' => (int) $usuario['id_usuario'],
                'nombres'    => $usuario['nombres'],
                'email'      => $usuario['email'],
                'rol'        => $usuario['rol'],
                'csrf_token' => Auth::generateCsrfToken(),
            ]
        ));
    }

    public function destroy(): void
    {
        $this->validateCsrfOrFail();

        $id_usuario = (int) ($_POST['id_usuario'] ?? 0);
        if ($id_usuario <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $userModel = new User();

        if ($userModel->delete($id_usuario)) {
            $this->flash('Se eliminó el usuario exitosamente', 'success');
            $this->redirect(BASE_URL . '/users');
        }

        $this->flash('Error al eliminar el usuario', 'error');
        $this->redirect(BASE_URL . '/users/delete/' . $id_usuario);
    }
}

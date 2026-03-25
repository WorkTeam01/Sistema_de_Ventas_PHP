<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    private function url(): string
    {
        return rtrim($_ENV['APP_URL'], '/');
    }

    private function flash(string $mensaje, string $icono = 'success'): void
    {
        Auth::startSession();
        $_SESSION['mensaje'] = $mensaje;
        $_SESSION['icono'] = $icono;
    }

    private function validateCsrfOrFail(): void
    {
        if (!Auth::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            die('Error de seguridad: Token CSRF inválido.');
        }
    }

    private function getIdFromRequest(array $source): int
    {
        $id = isset($source['id']) ? (int) $source['id'] : (int) ($source['id_usuario'] ?? 0);
        return $id;
    }

    public function index(): void
    {
        Auth::startSession();
        $URL = $this->url();
        $usuarioSesion = Auth::user() ?? [];

        $userModel = new User();
        $usuarios_datos = $userModel->findAllWithRole();

        $this->view('views/users/index.php', [
            'URL' => $URL,
            'nombres_sesion' => $usuarioSesion['nombres'] ?? '',
            'rol_sesion' => $usuarioSesion['rol'] ?? '',
            'usuarios_datos' => $usuarios_datos,
        ]);
    }

    public function create(): void
    {
        Auth::startSession();
        $URL = $this->url();
        $usuarioSesion = Auth::user() ?? [];

        $userModel = new User();
        $roles_datos = $userModel->findAllRoles();

        $this->view('views/users/create.php', [
            'URL' => $URL,
            'nombres_sesion' => $usuarioSesion['nombres'] ?? '',
            'rol_sesion' => $usuarioSesion['rol'] ?? '',
            'roles_datos' => $roles_datos,
            'csrf_token' => Auth::generateCsrfToken(),
        ]);
    }

    public function store(): void
    {
        $URL = $this->url();
        $this->validateCsrfOrFail();

        $nombres = trim($_POST['nombres'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = (int) ($_POST['rol'] ?? 0);
        $password_user = $_POST['password_user'] ?? '';
        $password_repeat = $_POST['password_repeat'] ?? '';

        if ($nombres === '' || $email === '' || $rol <= 0 || $password_user === '' || $password_repeat === '') {
            $this->flash('Todos los campos son obligatorios.', 'error');
            $this->redirect($URL . '/users/create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('El formato del correo electrónico es inválido.', 'error');
            $this->redirect($URL . '/users/create');
        }

        if ($password_user !== $password_repeat) {
            $this->flash('Las contraseñas no coinciden', 'error');
            $this->redirect($URL . '/users/create');
        }

        $userModel = new User();
        if ($userModel->emailExists($email)) {
            $this->flash('El correo electrónico ya está registrado.', 'error');
            $this->redirect($URL . '/users/create');
        }

        $passwordHash = password_hash($password_user, PASSWORD_DEFAULT);
        $ok = $userModel->createUser($nombres, $email, $rol, $passwordHash);

        if ($ok) {
            $this->flash('El usuario se registró exitosamente', 'success');
            $this->redirect($URL . '/users');
        }

        $this->flash('No se pudo registrar el usuario.', 'error');
        $this->redirect($URL . '/users/create');
    }

    public function show(?int $id = null): void
    {
        Auth::startSession();
        $URL = $this->url();
        $usuarioSesion = Auth::user() ?? [];

        $id = $id ?? $this->getIdFromRequest($_GET);
        if ($id <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect($URL . '/users');
        }

        $userModel = new User();
        $usuario = $userModel->findWithRoleById($id);

        if (!$usuario) {
            $this->flash('No se encontró el usuario solicitado.', 'error');
            $this->redirect($URL . '/users');
        }

        $this->view('views/users/show.php', [
            'URL' => $URL,
            'nombres_sesion' => $usuarioSesion['nombres'] ?? '',
            'rol_sesion' => $usuarioSesion['rol'] ?? '',
            'usuario_data' => $usuario,
        ]);
    }

    public function edit(?int $id = null): void
    {
        Auth::startSession();
        $URL = $this->url();
        $usuarioSesion = Auth::user() ?? [];

        $id = $id ?? $this->getIdFromRequest($_GET);
        if ($id <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect($URL . '/users');
        }

        $userModel = new User();
        $usuario = $userModel->findWithRoleById($id);

        if (!$usuario) {
            $this->flash('No se encontró el usuario solicitado.', 'error');
            $this->redirect($URL . '/users');
        }

        $roles_datos = $userModel->findAllRoles();

        $this->renderWithLayout('views/users/edit.php', [
            'URL' => $URL,
            'nombres_sesion' => $usuarioSesion['nombres'] ?? '',
            'rol_sesion' => $usuarioSesion['rol'] ?? '',
            'id_usuario' => (int) ($usuario['id_usuario'] ?? 0),
            'nombres' => $usuario['nombres'] ?? '',
            'email' => $usuario['email'] ?? '',
            'idRolActual' => (int) ($usuario['id_rol'] ?? 0),
            'rolActual' => $usuario['rol'] ?? '',
            'roles_datos' => $roles_datos,
            'csrf_token' => Auth::generateCsrfToken(),
        ]);
    }

    public function update(?int $id = null): void
    {
        $URL = $this->url();
        $this->validateCsrfOrFail();

        $id_usuario = $id ?? $this->getIdFromRequest($_POST);
        $nombres = trim($_POST['nombres'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = (int) ($_POST['rol'] ?? 0);
        $password_user = $_POST['password_user'] ?? '';
        $password_repeat = $_POST['password_repeat'] ?? '';

        if ($id_usuario <= 0 || $nombres === '' || $email === '' || $rol <= 0) {
            $this->flash('Datos inválidos para actualizar el usuario.', 'error');
            $this->redirect($URL . '/users');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('El formato del correo electrónico es inválido.', 'error');
            $this->redirect($URL . '/users/edit/' . $id_usuario);
        }

        $userModel = new User();
        if ($userModel->emailExists($email, $id_usuario)) {
            $this->flash('El correo electrónico ya está registrado por otro usuario.', 'error');
            $this->redirect($URL . '/users/edit/' . $id_usuario);
        }

        $passwordHash = null;
        if ($password_user !== '') {
            if ($password_user !== $password_repeat) {
                $this->flash('Las contraseñas no coinciden', 'error');
                $this->redirect($URL . '/users/edit/' . $id_usuario);
            }
            $passwordHash = password_hash($password_user, PASSWORD_DEFAULT);
        }

        $ok = $userModel->updateUser($id_usuario, $nombres, $email, $rol, $passwordHash);

        if ($ok) {
            $this->flash('El usuario se actualizó exitosamente', 'success');
            $this->redirect($URL . '/users');
        }

        $this->flash('No se pudo actualizar el usuario.', 'error');
        $this->redirect($URL . '/users/edit/' . $id_usuario);
    }

    public function delete(?int $id = null): void
    {
        Auth::startSession();
        $URL = $this->url();
        $usuarioSesion = Auth::user() ?? [];

        $id = $id ?? $this->getIdFromRequest($_GET);
        if ($id <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect($URL . '/users');
        }

        $userModel = new User();
        $usuario = $userModel->findWithRoleById($id);

        if (!$usuario) {
            $this->flash('No se encontró el usuario solicitado.', 'error');
            $this->redirect($URL . '/users');
        }

        $this->renderWithLayout('views/users/delete.php', [
            'URL' => $URL,
            'nombres_sesion' => $usuarioSesion['nombres'] ?? '',
            'rol_sesion' => $usuarioSesion['rol'] ?? '',
            'id_usuario' => (int) ($usuario['id_usuario'] ?? 0),
            'nombres' => $usuario['nombres'] ?? '',
            'email' => $usuario['email'] ?? '',
            'rol' => $usuario['rol'] ?? '',
            'csrf_token' => Auth::generateCsrfToken(),
        ]);
    }

    public function destroy(?int $id = null): void
    {
        $URL = $this->url();
        $this->validateCsrfOrFail();

        $id_usuario = $id ?? $this->getIdFromRequest($_POST);
        if ($id_usuario <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect($URL . '/users');
        }

        $userModel = new User();

        if ($userModel->delete($id_usuario)) {
            $this->flash('Se eliminó el usuario exitosamente', 'success');
            $this->redirect($URL . '/users');
        }

        $this->flash('Error al eliminar el usuario', 'error');
        $this->redirect($URL . '/users/delete/' . $id_usuario);
    }
}

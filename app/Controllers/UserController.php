<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Muestra el listado de todos los usuarios con su rol asignado.
     */
    public function index(): void
    {
        $userModel = new User();
        $usuarios_datos = $userModel->findAllWithRole();

        $this->renderWithLayout('views/users/index.php', array_merge(
            $this->sessionData(),
            [
                'usuarios_datos' => $usuarios_datos,
                'pageScripts' => ['/js/modules/users/users-index.js'],
            ]
        ), true, ['datatable']);
    }

    /**
     * Muestra el formulario para registrar un nuevo usuario.
     */
    public function create(): void
    {
        $userModel = new User();
        $roles_datos = $userModel->findAllRoles();

        $this->renderWithLayout('views/users/create.php', array_merge(
            $this->sessionData(),
            [
                'roles_datos' => $roles_datos,
                'csrf_token' => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/users/users-create.js'],
            ]
        ), true, ['select2', 'validation']);
    }

    /**
     * Procesa el formulario de creación, valida datos y guarda el nuevo usuario.
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $nombres = trim($_POST['nombres'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = (int)($_POST['rol'] ?? 0);
        $password_user = $_POST['password_user'] ?? '';
        $password_repeat = $_POST['password_repeat'] ?? '';

        if ($nombres === '' || $email === '' || $rol <= 0 || $password_user === '' || $password_repeat === '') {
            $this->flash('Todos los campos son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/users/create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('El formato del correo electrónico es inválido.', 'error');
            $this->redirect(BASE_URL . '/users/create');
        }

        if (strlen($password_user) < 6) {
            $this->flash('La contraseña debe tener al menos 6 caracteres.', 'error');
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

        $newUserId = $userModel->createUser($nombres, $email, $rol, $password_user);
        if ($newUserId !== false) {
            $nuevo = $userModel->findWithRoleById($newUserId);
            ActivityLog::record(
                'create', 'user', $newUserId,
                "Usuario '{$nombres}' ({$email}) creado — rol: " . ($nuevo['rol'] ?? ''),
                null,
                ['nombres' => $nombres, 'email' => $email, 'rol' => $nuevo['rol'] ?? null]
            );
            $this->flash('El usuario se registró exitosamente', 'success');
            $this->redirect(BASE_URL . '/users');
        }

        $this->flash('No se pudo registrar el usuario.', 'error');
        $this->redirect(BASE_URL . '/users/create');
    }

    /**
     * Muestra el formulario de edición para un usuario existente.
     *
     * @param int|null $id ID del usuario a editar
     */
    public function edit(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $userModel = new User();
        $usuario = $userModel->findWithRoleById($id);

        if (!$usuario) {
            $this->flash('No se encontró el usuario solicitado.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $roles_datos = $userModel->findAllRoles();

        $this->renderWithLayout('views/users/edit.php', array_merge(
            $this->sessionData(),
            [
                'id_usuario' => (int)$usuario['id_usuario'],
                'nombres' => $usuario['nombres'],
                'email' => $usuario['email'],
                'idRolActual' => (int)$usuario['id_rol'],
                'rolActual' => $usuario['rol'],
                'roles_datos' => $roles_datos,
                'csrf_token' => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/users/users-edit.js'],
            ]
        ), true, ['select2', 'validation']);
    }

    /**
     * Procesa el formulario de edición y actualiza los datos del usuario.
     * El cambio de contraseña es opcional; solo se aplica si se envía un valor.
     */
    public function update(): void
    {
        $this->validateCsrfOrFail();

        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        $nombres = trim($_POST['nombres'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = (int)($_POST['rol'] ?? 0);
        $password_user = $_POST['password_user'] ?? '';
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
        $usuarioActual = $userModel->findWithRoleById($id_usuario);

        if ($userModel->emailExists($email, $id_usuario)) {
            $this->flash('El correo electrónico ya está registrado por otro usuario.', 'error');
            $this->redirect(BASE_URL . '/users/edit/' . $id_usuario);
        }

        $newPassword = null;
        if ($password_user !== '') {
            if (strlen($password_user) < 6) {
                $this->flash('La contraseña debe tener al menos 6 caracteres.', 'error');
                $this->redirect(BASE_URL . '/users/edit/' . $id_usuario);
            }
            if ($password_user !== $password_repeat) {
                $this->flash('Las contraseñas no coinciden', 'error');
                $this->redirect(BASE_URL . '/users/edit/' . $id_usuario);
            }
            $newPassword = $password_user;
        }

        if ($userModel->updateUser($id_usuario, $nombres, $email, $rol, $newPassword)) {
            if ($usuarioActual && (int)$usuarioActual['id_rol'] !== $rol) {
                $usuarioNuevo = $userModel->findWithRoleById($id_usuario);
                ActivityLog::record(
                    'role_change', 'user', $id_usuario,
                    "Cambio de rol para '{$usuarioActual['nombres']}'",
                    [
                        'nombres' => $usuarioActual['nombres'],
                        'email' => $usuarioActual['email'],
                        'id_rol' => $usuarioActual['id_rol'],
                        'rol' => $usuarioActual['rol'],
                    ],
                    [
                        'nombres' => $nombres,
                        'email' => $email,
                        'id_rol' => $rol,
                        'rol' => $usuarioNuevo['rol'] ?? null,
                    ]
                );
            }
            $this->flash('El usuario se actualizó exitosamente', 'success');
            $this->redirect(BASE_URL . '/users');
        }

        $this->flash('No se pudo actualizar el usuario.', 'error');
        $this->redirect(BASE_URL . '/users/edit/' . $id_usuario);
    }

    /**
     * Verifica si el usuario tiene registros asociados en otras tablas.
     * Responde JSON: { referenced: bool, productos: int, compras: int }
     *
     * @param int|null $id ID del usuario a verificar
     */
    public function check(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['error' => 'Usuario inválido.'], 400);
        }

        $userModel = new User();

        if (!$userModel->find($id)) {
            $this->json(['error' => 'Usuario no encontrado.'], 404);
        }

        $counts = $userModel->getReferenceCount($id);

        $this->json([
            'referenced' => ($counts['productos'] + $counts['compras']) > 0,
            'productos' => $counts['productos'],
            'compras' => $counts['compras'],
        ]);
    }

    /**
     * Verifica vía AJAX si un email ya está registrado.
     * Responde true (libre) o string de error (ocupado) para jquery.validate remote.
     */
    public function checkEmail(): void
    {
        $email = trim($_POST['email'] ?? '');
        $id = $_POST['id_usuario'] ?? null;

        if ($id === '' || $id === 'null') {
            $id = null;
        } elseif ($id !== null) {
            $id = (int)$id;
        }

        $userModel = new User();
        if ($userModel->emailExists($email, $id)) {
            echo json_encode('El correo electrónico ya está registrado en el sistema');
        } else {
            echo json_encode(true);
        }
    }

    /**
     * Muestra la pantalla de confirmación antes de eliminar un usuario.
     *
     * @param int|null $id ID del usuario a eliminar
     */
    public function delete(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $userModel = new User();
        $usuario = $userModel->findWithRoleById($id);

        if (!$usuario) {
            $this->flash('No se encontró el usuario solicitado.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        $this->renderWithLayout('views/users/delete.php', array_merge(
            $this->sessionData(),
            [
                'id_usuario' => (int)$usuario['id_usuario'],
                'nombres' => $usuario['nombres'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol'],
                'csrf_token' => Auth::generateCsrfToken(),
            ]
        ));
    }

    /**
     * Muestra la página de perfil del usuario autenticado.
     * Prepara las variables de presentación (iniciales, fecha, color por rol).
     */
    public function profile(): void
    {
        $authUser = Auth::user();
        $userModel = new User();
        $usuario = $userModel->findWithRoleById((int)$authUser['id_usuario']);

        if (!$usuario) {
            $this->flash('No se encontró el usuario.', 'error');
            $this->redirect(BASE_URL . '/');
        }

        // Iniciales del usuario (máximo 2 caracteres)
        $words = preg_split('/\s+/', trim($usuario['nombres']));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $word) {
            if ($word !== '') {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }
        }

        // Fecha de registro formateada
        $fechaRegistro = 'N/D';
        if (!empty($usuario['fyh_creacion'])) {
            $dt = date_create($usuario['fyh_creacion']);
            $fechaRegistro = $dt ? $dt->format('d/m/Y') : 'N/D';
        }

        // Clases de color según rol
        $roleConfig = [
            'Administrador' => ['card' => 'card-danger', 'badge' => 'badge-danger'],
            'Vendedor' => ['card' => 'card-success', 'badge' => 'badge-success'],
            'Comprador' => ['card' => 'card-warning', 'badge' => 'badge-warning'],
        ];
        $config = $roleConfig[$usuario['rol']] ?? ['card' => 'card-primary', 'badge' => 'badge-primary'];

        $validTabs = ['perfil', 'password'];
        $activeTab = in_array($_GET['tab'] ?? '', $validTabs) ? $_GET['tab'] : 'perfil';

        // Variables de presentación listas para la vista (sin lógica en la vista)
        $csrfToken = Auth::generateCsrfToken();

        $this->renderWithLayout('views/users/profile.php', array_merge(
            $this->sessionData(),
            [
                'id_usuario' => (int)$usuario['id_usuario'],
                'cardClass' => $config['card'],
                'badgeClass' => $config['badge'],
                'nombresSafe' => htmlspecialchars($usuario['nombres'], ENT_QUOTES, 'UTF-8'),
                'emailSafe' => htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'),
                'rolSafe' => htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8'),
                'initSafe' => htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'),
                'fechaSafe' => htmlspecialchars($fechaRegistro, ENT_QUOTES, 'UTF-8'),
                'csrfSafe' => htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'),
                'checkUrl' => htmlspecialchars(BASE_URL . '/users/check-email', ENT_QUOTES, 'UTF-8'),
                'navPerfilClass' => $activeTab === 'perfil' ? 'active' : '',
                'navPasswordClass' => $activeTab === 'password' ? 'active' : '',
                'panePerfilClass' => $activeTab === 'perfil' ? 'show active' : '',
                'panePasswordClass' => $activeTab === 'password' ? 'show active' : '',
                'pageStyles' => ['/css/modules/users/profile.css'],
                'pageScripts' => ['/js/modules/users/users-profile.js'],
            ]
        ), true, ['validation']);
    }

    /**
     * Actualiza nombre y email del perfil propio del usuario autenticado.
     */
    public function updateProfile(): void
    {
        $this->validateCsrfOrFail();

        $authUser = Auth::user();
        $id_usuario = (int)$authUser['id_usuario'];
        $nombres = trim($_POST['nombres'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($nombres === '' || $email === '') {
            $this->flash('El nombre y el email son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/profile');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('El formato del correo electrónico es inválido.', 'error');
            $this->redirect(BASE_URL . '/profile');
        }

        $userModel = new User();
        if ($userModel->emailExists($email, $id_usuario)) {
            $this->flash('El correo electrónico ya está registrado por otro usuario.', 'error');
            $this->redirect(BASE_URL . '/profile');
        }

        if ($userModel->updateProfileInfo($id_usuario, $nombres, $email)) {
            if ($email !== $authUser['email']) {
                Auth::startSession();
                $_SESSION['sesion_email'] = $email;
            }
            $this->flash('Perfil actualizado exitosamente.', 'success');
            $this->redirect(BASE_URL . '/profile');
        }

        $this->flash('No se pudo actualizar el perfil.', 'error');
        $this->redirect(BASE_URL . '/profile');
    }

    /**
     * Actualiza únicamente la contraseña del usuario autenticado.
     */
    public function updatePassword(): void
    {
        $this->validateCsrfOrFail();

        $authUser = Auth::user();
        $id_usuario = (int)$authUser['id_usuario'];
        $password_user = $_POST['password_user'] ?? '';
        $password_repeat = $_POST['password_repeat'] ?? '';

        if ($password_user === '') {
            $this->flash('La nueva contraseña es obligatoria.', 'error');
            $this->redirect(BASE_URL . '/profile?tab=password');
        }

        if (strlen($password_user) < 6) {
            $this->flash('La contraseña debe tener al menos 6 caracteres.', 'error');
            $this->redirect(BASE_URL . '/profile?tab=password');
        }

        if ($password_user !== $password_repeat) {
            $this->flash('Las contraseñas no coinciden.', 'error');
            $this->redirect(BASE_URL . '/profile?tab=password');
        }

        $userModel = new User();

        if ($userModel->updatePassword($id_usuario, $password_user)) {
            $this->flash('Contraseña actualizada exitosamente.', 'success');
            $this->redirect(BASE_URL . '/profile?tab=password');
        }

        $this->flash('No se pudo actualizar la contraseña.', 'error');
        $this->redirect(BASE_URL . '/profile?tab=password');
    }

    /**
     * Elimina definitivamente un usuario de la base de datos.
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFail();

        $id_usuario = (int)($_POST['id_usuario'] ?? 0);
        if ($id_usuario <= 0) {
            $this->flash('Usuario inválido.', 'error');
            $this->redirect(BASE_URL . '/users');
        }

        if ($id_usuario === (int)Auth::user()['id_usuario']) {
            $this->flash('No puedes eliminar tu propio usuario.', 'error');
            $this->redirect(BASE_URL . '/users');
            return;
        }

        $userModel = new User();

        if ($userModel->isReferenced($id_usuario)) {
            $this->flash('No se puede eliminar: el usuario tiene registros asociados.', 'error');
            $this->redirect(BASE_URL . '/users');
            return;
        }

        $snapshot = $userModel->findWithRoleById($id_usuario);

        if ($userModel->delete($id_usuario)) {
            if ($snapshot) {
                ActivityLog::record(
                    'delete', 'user', $id_usuario,
                    "Usuario '{$snapshot['nombres']}' ({$snapshot['email']}) eliminado — rol: {$snapshot['rol']}",
                    ['nombres' => $snapshot['nombres'], 'email' => $snapshot['email'], 'rol' => $snapshot['rol']]
                );
            }
            $this->flash('Se eliminó el usuario exitosamente', 'success');
            $this->redirect(BASE_URL . '/users');
        }

        $this->flash('Error al eliminar el usuario', 'error');
        $this->redirect(BASE_URL . '/users/delete/' . $id_usuario);
    }
}

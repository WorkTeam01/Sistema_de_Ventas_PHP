<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * Muestra el listado de todos los roles con modales para crear y editar.
     */
    public function index(): void
    {
        $roleModel = new Role();
        $roles_datos = $roleModel->all();

        $this->renderWithLayout('views/roles/index.php', array_merge(
            $this->sessionData(),
            [
                'roles_datos' => $roles_datos,
                'csrf_token'  => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/roles/roles-datatable.js', '/js/modules/roles/roles-modals.js'],
            ]
        ), true, ['datatable', 'validation']);
    }

    /**
     * Guarda un nuevo rol (AJAX).
     */
    public function store(): void
    {
        $this->validateCsrfOrFailJson();

        $rol = trim($this->input('rol') ?? '');

        if ($rol === '') {
            $this->json(['success' => false, 'message' => 'El nombre del rol es obligatorio.']);
            return;
        }

        $roleModel = new Role();

        if ($roleModel->nameExists($rol)) {
            $this->json(['success' => false, 'message' => 'Ya existe un rol con ese nombre.']);
            return;
        }

        if ($roleModel->create(['rol' => $rol])) {
            $this->json(['success' => true, 'message' => 'Rol creado exitosamente.']);
            return;
        }

        $this->json(['success' => false, 'message' => 'Error al crear el rol.']);
    }

    /**
     * Retorna los datos de un rol en JSON (AJAX — para pre-llenar el modal de edición).
     *
     * @param int|null $id
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de rol inválido.']);
            return;
        }

        $roleModel = new Role();
        $role = $roleModel->find($id);

        if ($role) {
            $this->json(['success' => true, 'data' => $role]);
            return;
        }

        $this->json(['success' => false, 'message' => 'Rol no encontrado.']);
    }

    /**
     * Actualiza un rol existente (AJAX).
     *
     * @param int|null $id
     */
    public function update(?int $id = null): void
    {
        $this->validateCsrfOrFailJson();

        $id = $id ?? (int)($_POST['id'] ?? 0);
        $rol = trim($this->input('rol') ?? '');

        if ($id <= 0 || $rol === '') {
            $this->json(['success' => false, 'message' => 'Datos inválidos para actualizar el rol.']);
            return;
        }

        $roleModel = new Role();

        if (!$roleModel->find($id)) {
            $this->json(['success' => false, 'message' => 'Rol no encontrado.']);
            return;
        }

        if ($roleModel->nameExists($rol, $id)) {
            $this->json(['success' => false, 'message' => 'Ya existe otro rol con ese nombre.']);
            return;
        }

        if ($roleModel->update($id, ['rol' => $rol])) {
            $this->json(['success' => true, 'message' => 'Rol actualizado exitosamente.']);
            return;
        }

        $this->json(['success' => false, 'message' => 'Error al actualizar el rol.']);
    }

    /**
     * Verifica si el nombre del rol ya existe (AJAX — jQuery Validate remote).
     *
     * jQuery Validate espera:
     * - true  → validación pasa (nombre disponible)
     * - string → validación falla (mensaje de error)
     */
    public function checkNombre(): void
    {
        $rol = trim($this->input('rol') ?? '');
        $id = $this->input('id');

        if ($id === '' || $id === 'null') {
            $id = null;
        } elseif ($id !== null) {
            $id = (int)$id;
        }

        if ($rol === '') {
            echo json_encode(true);
            exit;
        }

        $roleModel = new Role();
        $exists = $roleModel->nameExists($rol, $id);

        echo json_encode($exists ? 'Ya existe un rol con este nombre.' : true);
        exit;
    }

    /**
     * Muestra la vista de asignación de permisos de un rol
     * (checkboxes agrupados por módulo).
     *
     * @param int|null $id
     */
    public function permisos(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        $roleModel = new Role();
        $role = $roleModel->find($id);

        if (!$role) {
            $this->flash('Rol no encontrado.', 'error');
            $this->redirect(BASE_URL . '/roles');
        }

        $permissionModel = new Permission();

        $this->renderWithLayout('views/roles/permisos.php', array_merge(
            $this->sessionData(),
            [
                'role'                => $role,
                'permisos_agrupados'  => $permissionModel->allGroupedByModulo(),
                'permisos_asignados'  => $roleModel->getAssignedPermissionIds($id),
                'csrf_token'          => Auth::generateCsrfToken(),
                'pageScripts'         => ['/js/modules/roles/roles-permisos.js'],
            ]
        ));
    }

    /**
     * Sincroniza el conjunto de permisos asignados a un rol (AJAX).
     * Incrementa permisos_version para invalidar la caché de sesión de otros usuarios
     * del rol; si el admin edita su propio rol, refresca sus permisos de inmediato.
     *
     * @param int|null $id
     */
    public function syncPermisos(?int $id = null): void
    {
        $this->validateCsrfOrFailJson();

        $id = $id ?? (int)($_POST['id'] ?? 0);

        $roleModel = new Role();

        if ($id <= 0 || !$roleModel->find($id)) {
            $this->json(['success' => false, 'message' => 'Rol no encontrado.']);
        }

        $permisos = array_map('intval', (array)($_POST['permisos'] ?? []));

        if (!$roleModel->syncPermissions($id, $permisos)) {
            $this->json(['success' => false, 'message' => 'Error al actualizar los permisos del rol.']);
        }

        $usuario = Auth::user();
        if ($usuario && (int)$usuario['id_rol'] === $id) {
            Auth::refreshPermissions();
        }

        $this->json(['success' => true, 'message' => 'Permisos actualizados exitosamente.']);
    }
}

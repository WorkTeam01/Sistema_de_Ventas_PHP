<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Muestra el listado de todos los permisos con modales para crear y editar.
     */
    public function index(): void
    {
        $permissionModel = new Permission();
        $permisos_datos = $permissionModel->all();

        $this->renderWithLayout('views/permissions/index.php', array_merge(
            $this->sessionData(),
            [
                'permisos_datos' => $permisos_datos,
                'csrf_token'     => Auth::generateCsrfToken(),
                'pageScripts'    => ['/js/modules/permissions/permissions-datatable.js', '/js/modules/permissions/permissions-modals.js'],
            ]
        ), true, ['datatable', 'validation']);
    }

    /**
     * Guarda un nuevo permiso (AJAX).
     */
    public function store(): void
    {
        $this->validateCsrfOrFailJson();

        $clave = trim($this->input('clave') ?? '');
        $descripcion = trim($this->input('descripcion') ?? '');
        $modulo = trim($this->input('modulo') ?? '');

        if ($clave === '' || $descripcion === '' || $modulo === '') {
            $this->json(['success' => false, 'message' => 'Los campos Clave, Descripción y Módulo son obligatorios.']);
        }

        $permissionModel = new Permission();

        if ($permissionModel->claveExists($clave)) {
            $this->json(['success' => false, 'message' => 'Ya existe un permiso con esa clave.']);
        }

        if ($permissionModel->create(['clave' => $clave, 'descripcion' => $descripcion, 'modulo' => $modulo])) {
            $this->json(['success' => true, 'message' => 'El permiso se registró exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al registrar el permiso.']);
    }

    /**
     * Retorna los datos de un permiso en JSON (AJAX — para pre-llenar el modal de edición).
     *
     * @param int|null $id
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de permiso inválido.']);
        }

        $permissionModel = new Permission();
        $permiso = $permissionModel->find($id);

        if ($permiso) {
            $this->json(['success' => true, 'data' => $permiso]);
        }

        $this->json(['success' => false, 'message' => 'Permiso no encontrado.']);
    }

    /**
     * Actualiza un permiso existente (AJAX).
     *
     * @param int|null $id
     */
    public function update(?int $id = null): void
    {
        $this->validateCsrfOrFailJson();

        $id = $id ?? (int)($_POST['id'] ?? 0);

        $clave = trim($this->input('clave') ?? '');
        $descripcion = trim($this->input('descripcion') ?? '');
        $modulo = trim($this->input('modulo') ?? '');

        if ($id <= 0 || $clave === '' || $descripcion === '' || $modulo === '') {
            $this->json(['success' => false, 'message' => 'Datos inválidos para actualizar el permiso.']);
        }

        $permissionModel = new Permission();

        if (!$permissionModel->find($id)) {
            $this->json(['success' => false, 'message' => 'Permiso no encontrado.']);
        }

        if ($permissionModel->claveExists($clave, $id)) {
            $this->json(['success' => false, 'message' => 'Ya existe otro permiso con esa clave.']);
        }

        if ($permissionModel->update($id, ['clave' => $clave, 'descripcion' => $descripcion, 'modulo' => $modulo])) {
            $this->json(['success' => true, 'message' => 'El permiso se actualizó exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al actualizar el permiso.']);
    }

    /**
     * Verifica si la clave del permiso ya existe (AJAX — jQuery Validate remote).
     *
     * jQuery Validate espera:
     * - true  → validación pasa (clave disponible)
     * - string → validación falla (mensaje de error)
     */
    public function checkClave(): void
    {
        $clave = trim($this->input('clave') ?? '');
        $id = $this->input('id');

        if ($id === '' || $id === 'null') {
            $id = null;
        } elseif ($id !== null) {
            $id = (int)$id;
        }

        if ($clave === '') {
            echo json_encode(true);
            exit;
        }

        $permissionModel = new Permission();
        $exists = $permissionModel->claveExists($clave, $id);

        echo json_encode($exists ? 'Ya existe un permiso con esta clave.' : true);
        exit;
    }
}

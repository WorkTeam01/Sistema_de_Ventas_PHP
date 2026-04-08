<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Supplier;

class SupplierController extends Controller
{
    /**
     * Muestra el listado de todos los proveedores con modales para crear y editar.
     */
    public function index(): void
    {
        $supplierModel = new Supplier();
        $suppliers_datos = $supplierModel->all();

        $this->renderWithLayout('views/suppliers/index.php', array_merge(
            $this->sessionData(),
            [
                'suppliers_datos' => $suppliers_datos,
                'pageScripts' => ['/js/modules/suppliers/suppliers-datatable.js', '/js/modules/suppliers/suppliers-modals.js']
            ]
        ), true, ['datatable', 'validation']);
    }

    /**
     * Guarda un nuevo proveedor (AJAX).
     */
    public function store(): void
    {
        $nombre_proveedor = trim($this->input('nombre_proveedor') ?? '');
        $empresa = trim($this->input('empresa') ?? '');
        $celular = trim($this->input('celular') ?? '');
        $direccion = trim($this->input('direccion') ?? '');
        $telefono = $this->input('telefono');
        $email = $this->input('email');

        if ($nombre_proveedor === '' || $empresa === '' || $celular === '' || $direccion === '') {
            $this->json(['success' => false, 'message' => 'Los campos Nombre, Empresa, Celular y Dirección son obligatorios.']);
        }

        $supplierModel = new Supplier();

        if ($supplierModel->nameExists($empresa)) {
            $this->json(['success' => false, 'message' => 'Ya existe un proveedor con esa empresa.']);
        }

        if ($supplierModel->createSupplier($nombre_proveedor, $empresa, $celular, $direccion, $telefono, $email)) {
            $this->json(['success' => true, 'message' => 'El proveedor se registró exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al registrar el proveedor.']);
    }

    /**
     * Retorna los datos de un proveedor en JSON (AJAX — para pre-llenar el modal de edición).
     *
     * @param int|null $id
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID de proveedor inválido.']);
        }

        $supplierModel = new Supplier();
        $supplier = $supplierModel->find($id);

        if ($supplier) {
            $this->json(['success' => true, 'data' => $supplier]);
        }

        $this->json(['success' => false, 'message' => 'Proveedor no encontrado.']);
    }

    /**
     * Actualiza un proveedor existente (AJAX).
     *
     * @param int|null $id
     */
    public function update(?int $id = null): void
    {
        $id = $id ?? (int)($_POST['id'] ?? 0);

        $nombre_proveedor = trim($this->input('nombre_proveedor') ?? '');
        $empresa = trim($this->input('empresa') ?? '');
        $celular = trim($this->input('celular') ?? '');
        $direccion = trim($this->input('direccion') ?? '');
        $telefono = $this->input('telefono');
        $email = $this->input('email');

        if ($id <= 0 || $nombre_proveedor === '' || $empresa === '' || $celular === '' || $direccion === '') {
            $this->json(['success' => false, 'message' => 'Datos inválidos para actualizar el proveedor.']);
        }

        $supplierModel = new Supplier();

        if (!$supplierModel->find($id)) {
            $this->json(['success' => false, 'message' => 'Proveedor no encontrado.']);
        }

        if ($supplierModel->nameExists($empresa, $id)) {
            $this->json(['success' => false, 'message' => 'Ya existe otro proveedor con esa empresa.']);
        }

        if ($supplierModel->updateSupplier($id, $nombre_proveedor, $empresa, $celular, $direccion, $telefono, $email)) {
            $this->json(['success' => true, 'message' => 'El proveedor se actualizó exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al actualizar el proveedor.']);
    }

    /**
     * Elimina un proveedor si no tiene compras asociadas (AJAX).
     */
    public function destroy(): void
    {
        $id = (int)($this->input('id_proveedor') ?? 0);

        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Proveedor inválido.']);
        }

        $supplierModel = new Supplier();

        if ($supplierModel->isReferenced($id)) {
            $this->json(['success' => false, 'message' => 'No se puede eliminar el proveedor porque tiene compras registradas.']);
        }

        if ($supplierModel->delete($id)) {
            $this->json(['success' => true, 'message' => 'El proveedor se eliminó exitosamente.']);
        }

        $this->json(['success' => false, 'message' => 'Error al eliminar el proveedor.']);
    }

    /**
     * Verifica si el nombre de empresa ya existe (AJAX — jQuery Validate remote).
     *
     * jQuery Validate espera:
     * - true  → validación pasa (empresa disponible)
     * - string → validación falla (mensaje de error)
     */
    public function checkNombre(): void
    {
        $empresa = trim($this->input('empresa') ?? '');
        $id = $this->input('id');

        if ($id === '' || $id === 'null') {
            $id = null;
        } elseif ($id !== null) {
            $id = (int)$id;
        }

        if ($empresa === '') {
            echo json_encode(true);
            exit;
        }

        $supplierModel = new Supplier();
        $exists = $supplierModel->nameExists($empresa, $id);

        echo json_encode($exists ? 'Ya existe un proveedor con esta empresa.' : true);
        exit;
    }
}

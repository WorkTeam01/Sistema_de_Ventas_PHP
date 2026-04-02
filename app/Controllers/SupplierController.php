<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Supplier;

class SupplierController extends Controller
{
    /**
     * Muestra el listado de todos los proveedores registrados.
     */
    public function index(): void
    {
        $supplierModel    = new Supplier();
        $suppliers_datos = $supplierModel->all();

        $this->renderWithLayout('views/suppliers/index.php', array_merge(
            $this->sessionData(),
            [
                'suppliers_datos' => $suppliers_datos,
                'csrf_token'      => Auth::generateCsrfToken(),
            ]
        ), true, ['datatable']);
    }

    /**
     * Muestra el formulario para registrar un nuevo proveedor.
     */
    public function create(): void
    {
        $this->renderWithLayout('views/suppliers/create.php', array_merge(
            $this->sessionData(),
            ['csrf_token' => Auth::generateCsrfToken()]
        ));
    }

    /**
     * Procesa el formulario de creación y guarda el nuevo proveedor.
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $nombre_proveedor = trim($_POST['nombre_proveedor'] ?? '');
        $empresa          = trim($_POST['empresa'] ?? '');
        $celular          = trim($_POST['celular'] ?? '');
        $direccion        = trim($_POST['direccion'] ?? '');
        $telefono         = !empty($_POST['telefono']) ? trim($_POST['telefono']) : null;
        $email            = !empty($_POST['email']) ? trim($_POST['email']) : null;

        if ($nombre_proveedor === '' || $empresa === '' || $celular === '' || $direccion === '') {
            $this->flash('Los campos Nombre, Empresa, Celular y Dirección son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/suppliers/create');
            return;
        }

        $supplierModel = new Supplier();

        if ($supplierModel->create([
            'nombre_proveedor' => $nombre_proveedor,
            'empresa'          => $empresa,
            'celular'          => $celular,
            'telefono'         => $telefono,
            'email'            => $email,
            'direccion'        => $direccion,
        ])) {
            $this->flash('El proveedor se registró exitosamente.', 'success');
            $this->redirect(BASE_URL . '/suppliers');
            return;
        }

        $this->flash('Error al registrar el proveedor.', 'error');
        $this->redirect(BASE_URL . '/suppliers/create');
    }

    /**
     * Muestra el formulario de edición para un proveedor existente.
     *
     * @param int|null $id ID del proveedor a editar.
     */
    public function edit(?int $id = null): void
    {
        $id = $id ?? (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Proveedor inválido.', 'error');
            $this->redirect(BASE_URL . '/suppliers');
            return;
        }

        $supplierModel = new Supplier();
        $supplier      = $supplierModel->find($id);

        if (!$supplier) {
            $this->flash('No se encontró el proveedor solicitado.', 'error');
            $this->redirect(BASE_URL . '/suppliers');
            return;
        }

        $this->renderWithLayout('views/suppliers/edit.php', array_merge(
            $this->sessionData(),
            [
                'id_proveedor'     => (int) $supplier['id_proveedor'],
                'nombre_proveedor' => $supplier['nombre_proveedor'],
                'empresa'          => $supplier['empresa'],
                'celular'          => $supplier['celular'],
                'telefono'         => $supplier['telefono'] ?? '',
                'email'            => $supplier['email'] ?? '',
                'direccion'        => $supplier['direccion'],
                'csrf_token'       => Auth::generateCsrfToken(),
            ]
        ));
    }

    /**
     * Procesa el formulario de edición y actualiza el proveedor.
     */
    public function update(): void
    {
        $this->validateCsrfOrFail();

        $id_proveedor     = (int) ($_POST['id_proveedor'] ?? 0);
        $nombre_proveedor = trim($_POST['nombre_proveedor'] ?? '');
        $empresa          = trim($_POST['empresa'] ?? '');
        $celular          = trim($_POST['celular'] ?? '');
        $direccion        = trim($_POST['direccion'] ?? '');
        $telefono         = !empty($_POST['telefono']) ? trim($_POST['telefono']) : null;
        $email            = !empty($_POST['email']) ? trim($_POST['email']) : null;

        if ($id_proveedor <= 0 || $nombre_proveedor === '' || $empresa === '' || $celular === '' || $direccion === '') {
            $this->flash('Datos inválidos para actualizar el proveedor.', 'error');
            $this->redirect(BASE_URL . '/suppliers');
            return;
        }

        $supplierModel = new Supplier();

        if ($supplierModel->update($id_proveedor, [
            'nombre_proveedor' => $nombre_proveedor,
            'empresa'          => $empresa,
            'celular'          => $celular,
            'telefono'         => $telefono,
            'email'            => $email,
            'direccion'        => $direccion,
        ])) {
            $this->flash('El proveedor se actualizó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/suppliers');
            return;
        }

        $this->flash('Error al actualizar el proveedor.', 'error');
        $this->redirect(BASE_URL . '/suppliers/edit/' . $id_proveedor);
    }

    /**
     * Elimina un proveedor si no tiene compras asociadas.
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFail();

        $id_proveedor = (int) ($_POST['id_proveedor'] ?? 0);

        if ($id_proveedor <= 0) {
            $this->flash('Proveedor inválido.', 'error');
            $this->redirect(BASE_URL . '/suppliers');
            return;
        }

        $supplierModel = new Supplier();

        if ($supplierModel->isReferenced($id_proveedor)) {
            $this->flash('No se puede eliminar el proveedor porque tiene compras registradas.', 'error');
            $this->redirect(BASE_URL . '/suppliers');
            return;
        }

        if ($supplierModel->delete($id_proveedor)) {
            $this->flash('El proveedor se eliminó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/suppliers');
            return;
        }

        $this->flash('Error al eliminar el proveedor.', 'error');
        $this->redirect(BASE_URL . '/suppliers');
    }
}

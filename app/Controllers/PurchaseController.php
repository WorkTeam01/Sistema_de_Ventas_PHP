<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\PurchaseReportPdf;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;

class PurchaseController extends Controller
{
    /**
     * Listado de compras con datos de producto, proveedor y usuario.
     */
    public function index(): void
    {
        $purchaseModel = new Purchase();
        $purchases_datos = $purchaseModel->allWithDetails();

        $this->renderWithLayout('views/purchases/index.php', array_merge(
            $this->sessionData(),
            [
                'purchases_datos' => $purchases_datos,
                'csrf_token' => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/purchases/purchases-index.js'],
            ]
        ), true, ['datatable']);
    }

    /**
     * Muestra el formulario de creación de compra.
     */
    public function create(): void
    {
        $purchaseModel = new Purchase();
        $productModel = new Product();
        $supplierModel = new Supplier();

        $this->renderWithLayout('views/purchases/create.php', array_merge(
            $this->sessionData(),
            [
                'next_number' => $purchaseModel->nextNumber(),
                'products' => $productModel->all(),
                'suppliers' => $supplierModel->all(),
                'email_sesion' => Auth::user()['email'] ?? '',
                'csrf_token' => Auth::generateCsrfToken(),
                'pageStyles' => ['/css/modules/purchases/create.css'],
                'pageScripts' => ['/js/modules/purchases/purchases-create.js'],
            ]
        ), true, ['validation', 'select2']);
    }

    /**
     * Procesa la creación de una compra.
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $data = [
            'id_producto' => $_POST['id_producto'] ?? '',
            'nro_compra' => $_POST['nro_compra'] ?? '',
            'fecha_compra' => trim($_POST['fecha_compra'] ?? ''),
            'id_proveedor' => $_POST['id_proveedor'] ?? '',
            'comprobante' => trim($_POST['comprobante'] ?? ''),
            'precio_compra' => $_POST['precio_compra'] ?? '',
            'cantidad' => $_POST['cantidad'] ?? '',
        ];

        $purchaseModel = new Purchase();
        $validation = $purchaseModel->validateData($data);

        if ($validation !== true) {
            $this->flash(implode(' ', $validation), 'error');
            $this->redirect(BASE_URL . '/purchases/create');
            return;
        }

        $ok = $purchaseModel->storeWithStock([
            'id_producto' => (int)$data['id_producto'],
            'nro_compra' => (int)$data['nro_compra'],
            'fecha_compra' => $data['fecha_compra'],
            'id_proveedor' => (int)$data['id_proveedor'],
            'comprobante' => $data['comprobante'],
            'id_usuario' => Auth::user()['id_usuario'],
            'precio_compra' => (float)$data['precio_compra'],
            'cantidad' => (int)$data['cantidad'],
        ]);

        if ($ok) {
            $this->flash('La compra se registró exitosamente.', 'success');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $this->flash('Error al registrar la compra.', 'error');
        $this->redirect(BASE_URL . '/purchases/create');
    }

    /**
     * Muestra el detalle completo de una compra (read-only).
     *
     * @param int|null $id ID de la compra.
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Compra inválida.', 'error');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $purchaseModel = new Purchase();
        $purchase = $purchaseModel->findWithDetails($id);

        if (!$purchase) {
            $this->flash('No se encontró la compra solicitada.', 'error');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $this->renderWithLayout('views/purchases/show.php', array_merge(
            $this->sessionData(),
            [
                'id_compra' => (int)$purchase['id_compra'],
                'nro_compra' => $purchase['nro_compra'],
                'comprobante' => $purchase['comprobante'],
                'fecha_compra' => $purchase['fecha_compra'],
                'precio_compra' => $purchase['precio_compra'],
                'cantidad' => $purchase['cantidad'],
                'fyh_creacion' => $purchase['fyh_creacion'],
                'fyh_actualizacion' => $purchase['fyh_actualizacion'],
                'id_producto' => (int)$purchase['id_producto'],
                'codigo' => $purchase['codigo'],
                'nombre_producto' => $purchase['nombre_producto'],
                'descripcion_producto' => $purchase['descripcion_producto'],
                'imagen' => $purchase['imagen'],
                'stock' => $purchase['stock'],
                'precio_venta' => $purchase['precio_venta'],
                'nombre_categoria' => $purchase['nombre_categoria'],
                'nombre_proveedor' => $purchase['nombre_proveedor'],
                'empresa' => $purchase['empresa'],
                'email_proveedor' => $purchase['email_proveedor'],
                'celular' => $purchase['celular'],
                'telefono' => $purchase['telefono'],
                'direccion' => $purchase['direccion'],
                'email_usuario' => $purchase['email_usuario'],
            ]
        ));
    }

    /**
     * Muestra el formulario de edición de una compra.
     *
     * @param int|null $id ID de la compra.
     */
    public function edit(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Compra inválida.', 'error');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $purchaseModel = new Purchase();
        $purchase = $purchaseModel->findWithDetails($id);

        if (!$purchase) {
            $this->flash('No se encontró la compra solicitada.', 'error');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $productModel = new Product();
        $supplierModel = new Supplier();

        $this->renderWithLayout('views/purchases/edit.php', array_merge(
            $this->sessionData(),
            [
                'id_compra' => (int)$purchase['id_compra'],
                'id_producto' => (int)$purchase['id_producto'],
                'nro_compra' => $purchase['nro_compra'],
                'fecha_compra' => $purchase['fecha_compra'],
                'id_proveedor' => (int)$purchase['id_proveedor'],
                'comprobante' => $purchase['comprobante'],
                'precio_compra' => $purchase['precio_compra'],
                'cantidad' => $purchase['cantidad'],
                'old_id_producto' => (int)$purchase['id_producto'],
                'old_cantidad' => (int)$purchase['cantidad'],
                'email_sesion' => Auth::user()['email'] ?? '',
                'products' => $productModel->all(),
                'suppliers' => $supplierModel->all(),
                'csrf_token' => Auth::generateCsrfToken(),
                'pageStyles' => ['/css/modules/purchases/create.css'],
                'pageScripts' => ['/js/modules/purchases/purchases-edit.js'],
            ]
        ), true, ['validation', 'select2']);
    }

    /**
     * Procesa la actualización de una compra.
     */
    public function update(): void
    {
        $this->validateCsrfOrFail();

        $id_compra = (int)($_POST['id_compra'] ?? 0);
        $old_id_producto = (int)($_POST['old_id_producto'] ?? 0);
        $old_cantidad = (int)($_POST['old_cantidad'] ?? 0);

        if ($id_compra <= 0) {
            $this->flash('Compra inválida.', 'error');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $data = [
            'id_producto' => $_POST['id_producto'] ?? '',
            'nro_compra' => $_POST['nro_compra'] ?? '',
            'fecha_compra' => trim($_POST['fecha_compra'] ?? ''),
            'id_proveedor' => $_POST['id_proveedor'] ?? '',
            'comprobante' => trim($_POST['comprobante'] ?? ''),
            'precio_compra' => $_POST['precio_compra'] ?? '',
            'cantidad' => $_POST['cantidad'] ?? '',
        ];

        $purchaseModel = new Purchase();
        $validation = $purchaseModel->validateData($data);

        if ($validation !== true) {
            $this->flash(implode(' ', $validation), 'error');
            $this->redirect(BASE_URL . '/purchases/edit/' . $id_compra);
            return;
        }

        $ok = $purchaseModel->updateWithStock(
            [
                'id_compra' => $id_compra,
                'id_producto' => (int)$data['id_producto'],
                'nro_compra' => (int)$data['nro_compra'],
                'fecha_compra' => $data['fecha_compra'],
                'id_proveedor' => (int)$data['id_proveedor'],
                'comprobante' => $data['comprobante'],
                'id_usuario' => Auth::user()['id_usuario'],
                'precio_compra' => (float)$data['precio_compra'],
                'cantidad' => (int)$data['cantidad'],
            ],
            $old_id_producto,
            $old_cantidad
        );

        if ($ok) {
            $this->flash('La compra se actualizó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $this->flash('Error al actualizar la compra.', 'error');
        $this->redirect(BASE_URL . '/purchases/edit/' . $id_compra);
    }

    /**
     * Emite el comprobante PDF de una compra directamente al navegador (inline).
     *
     * @param int|null $id ID de la compra.
     */
    public function report(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Compra inválida.', 'error');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $purchaseModel = new Purchase();
        $purchase = $purchaseModel->findWithDetails($id);

        if (!$purchase) {
            $this->flash('No se encontró la compra solicitada.', 'error');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $comprador = $purchase['nombre_usuario'] ?? '';
        PurchaseReportPdf::generate($purchase, $comprador, $id);
    }

    /**
     * Elimina una compra y revierte el stock del producto.
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFail();

        $id_compra = (int)($_POST['id_compra'] ?? 0);
        $id_producto = (int)($_POST['id_producto'] ?? 0);
        $cantidad = (int)($_POST['cantidad'] ?? 0);

        if ($id_compra <= 0 || $id_producto <= 0) {
            $this->flash('Compra inválida.', 'error');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $purchaseModel = new Purchase();
        $ok = $purchaseModel->destroyWithStock($id_compra, $id_producto, $cantidad);

        if ($ok) {
            $this->flash('La compra se eliminó exitosamente.', 'success');
            $this->redirect(BASE_URL . '/purchases');
            return;
        }

        $this->flash('Error al eliminar la compra.', 'error');
        $this->redirect(BASE_URL . '/purchases');
    }
}

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\InvoicePdf;
use App\Models\CartItem;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sale;

class SaleController extends Controller
{
    /**
     * Listado de ventas con datos del cliente.
     */
    public function index(): void
    {
        $saleModel = new Sale();
        $sales_data = $saleModel->allWithDetails();

        $this->renderWithLayout('views/sales/index.php', array_merge(
            $this->sessionData(),
            [
                'sales_data' => $sales_data,
                'csrf_token' => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/sales/sales-index.js'],
            ]
        ), true, ['datatable']);
    }

    /**
     * Muestra la interfaz POS para crear una nueva venta.
     */
    public function create(): void
    {
        $saleModel = new Sale();
        $cartItemModel = new CartItem();
        $productModel = new Product();
        $clientModel = new Client();

        $nro_venta = $saleModel->nextNumber();
        $cart_items = $cartItemModel->getByNroVenta($nro_venta);

        $this->renderWithLayout('views/sales/create.php', array_merge(
            $this->sessionData(),
            [
                'nro_venta' => $nro_venta,
                'cart_items' => $cart_items,
                'products' => $productModel->allWithCategories(),
                'clients' => $clientModel->all(),
                'csrf_token' => Auth::generateCsrfToken(),
                'pageStyles' => ['/css/modules/sales/create.css'],
                'pageScripts' => ['/js/modules/sales/sales-create.js'],
            ]
        ), true, ['datatable']);
    }

    /**
     * Agrega un producto al carrito.
     */
    public function addToCart(): void
    {
        $this->validateCsrfOrFail();

        $nro_venta = (int)($_POST['nro_venta'] ?? 0);
        $id_producto = (int)($_POST['id_producto'] ?? 0);
        $cantidad = (int)($_POST['cantidad'] ?? 0);

        if ($nro_venta <= 0 || $id_producto <= 0 || $cantidad < 1) {
            $this->flash('Datos inválidos para agregar al carrito.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        $cartItemModel = new CartItem();
        $ok = $cartItemModel->addItem($nro_venta, $id_producto, $cantidad);

        if (!$ok) {
            $this->flash('Stock insuficiente para la cantidad solicitada.', 'warning');
        }

        $this->redirect(BASE_URL . '/sales/create');
    }

    /**
     * Elimina un ítem del carrito.
     */
    public function removeFromCart(): void
    {
        $this->validateCsrfOrFail();

        $id_carrito = (int)($_POST['id_carrito'] ?? 0);

        if ($id_carrito <= 0) {
            $this->flash('Ítem inválido.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        $cartItemModel = new CartItem();
        $cartItemModel->removeItem($id_carrito);

        $this->redirect(BASE_URL . '/sales/create');
    }

    /**
     * Finaliza la venta: inserta en tb_ventas y decrementa stock.
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $nro_venta = (int)($_POST['nro_venta'] ?? 0);
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $total_a_cancelar = $_POST['total_a_cancelar'] ?? '';

        if ($nro_venta <= 0 || $id_cliente <= 0 || $total_a_cancelar === '') {
            $this->flash('Todos los campos son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        if (!is_numeric($total_a_cancelar) || (float)$total_a_cancelar <= 0) {
            $this->flash('El monto total debe ser un valor numérico mayor a cero.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        $cartItemModel = new CartItem();
        // Early-return de UX: mensaje específico antes de llegar al modelo.
        // storeWithStock() también verifica carrito vacío internamente.
        if ($cartItemModel->countByNroVenta($nro_venta) === 0) {
            $this->flash('El carrito está vacío. Agrega productos antes de registrar la venta.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        $saleModel = new Sale();
        $ok = $saleModel->storeWithStock([
            'nro_venta' => $nro_venta,
            'id_cliente' => $id_cliente,
            'total_pagado' => (float)$total_a_cancelar,
        ]);

        if ($ok) {
            $this->flash('La venta se registró exitosamente.', 'success');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $this->flash('Error al registrar la venta. Intente nuevamente.', 'error');
        $this->redirect(BASE_URL . '/sales/create');
    }

    /**
     * Muestra el detalle completo de una venta (read-only).
     *
     * @param int|null $id ID de la venta.
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Venta inválida.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $saleModel = new Sale();
        $sale = $saleModel->findWithDetails($id);

        if (!$sale) {
            $this->flash('No se encontró la venta solicitada.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $this->renderWithLayout('views/sales/show.php', array_merge(
            $this->sessionData(),
            [
                'id_venta' => (int)$sale['id_venta'],
                'nro_venta' => $sale['nro_venta'],
                'total_pagado' => $sale['total_pagado'],
                'fyh_creacion' => $sale['fyh_creacion'],
                'fyh_actualizacion' => $sale['fyh_actualizacion'],
                'nombre_cliente' => $sale['nombre_cliente'],
                'nit_ci_cliente' => $sale['nit_ci_cliente'],
                'celular_cliente' => $sale['celular_cliente'],
                'email_cliente' => $sale['email_cliente'],
                'items' => $sale['items'],
            ]
        ));
    }

    /**
     * Muestra la página de confirmación de eliminación de una venta.
     *
     * @param int|null $id ID de la venta.
     */
    public function confirmDelete(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Venta inválida.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $saleModel = new Sale();
        $sale = $saleModel->findWithDetails($id);

        if (!$sale) {
            $this->flash('No se encontró la venta solicitada.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $this->renderWithLayout('views/sales/delete.php', array_merge(
            $this->sessionData(),
            [
                'id_venta' => (int)$sale['id_venta'],
                'nro_venta' => $sale['nro_venta'],
                'total_pagado' => $sale['total_pagado'],
                'fyh_creacion' => $sale['fyh_creacion'],
                'nombre_cliente' => $sale['nombre_cliente'],
                'nit_ci_cliente' => $sale['nit_ci_cliente'],
                'items' => $sale['items'],
                'csrf_token' => Auth::generateCsrfToken(),
            ]
        ));
    }

    /**
     * Genera la factura PDF de una venta usando TCPDF.
     * No usa renderWithLayout() — emite el PDF directamente inline vía InvoicePdf.
     *
     * @param int|null $id ID de la venta.
     */
    public function invoice(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Venta inválida.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $saleModel = new Sale();
        $sale = $saleModel->findWithDetails($id);

        if (!$sale) {
            $this->flash('No se encontró la venta solicitada.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $totals = $saleModel->computeInvoiceTotals($sale['items']);
        $vendedor = Auth::user()['nombres'] ?? '';

        InvoicePdf::generate($sale, $totals, $vendedor, $id);
    }

    /**
     * Elimina una venta, revirtiendo el stock de todos los productos del carrito.
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFail();

        $id_venta = (int)($_POST['id_venta'] ?? 0);

        if ($id_venta <= 0) {
            $this->flash('Venta inválida.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $saleModel = new Sale();
        $ok = $saleModel->destroyWithStock($id_venta);

        if ($ok) {
            $this->flash('La venta se eliminó exitosamente y el stock fue restaurado.', 'success');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $this->flash('Error al eliminar la venta.', 'error');
        $this->redirect(BASE_URL . '/sales');
    }
}

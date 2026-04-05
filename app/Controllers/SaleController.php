<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\NumberToWords;
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
        $saleModel  = new Sale();
        $sales_data = $saleModel->allWithDetails();

        $this->renderWithLayout('views/sales/index.php', array_merge(
            $this->sessionData(),
            [
                'sales_data'  => $sales_data,
                'csrf_token'  => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/sales/sales-index.js'],
            ]
        ), true, ['datatable']);
    }

    /**
     * Muestra la interfaz POS para crear una nueva venta.
     */
    public function create(): void
    {
        $saleModel     = new Sale();
        $cartItemModel = new CartItem();
        $productModel  = new Product();
        $clientModel   = new Client();

        $nro_venta = $saleModel->nextNumber();
        $cart_items = $cartItemModel->getByNroVenta($nro_venta);

        $this->renderWithLayout('views/sales/create.php', array_merge(
            $this->sessionData(),
            [
                'nro_venta'   => $nro_venta,
                'cart_items'  => $cart_items,
                'products'    => $productModel->allWithCategories(),
                'clients'     => $clientModel->all(),
                'csrf_token'  => Auth::generateCsrfToken(),
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

        $nro_venta   = (int) ($_POST['nro_venta']   ?? 0);
        $id_producto = (int) ($_POST['id_producto'] ?? 0);
        $cantidad    = (int) ($_POST['cantidad']    ?? 0);

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

        $id_carrito = (int) ($_POST['id_carrito'] ?? 0);

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

        $nro_venta       = (int) ($_POST['nro_venta']       ?? 0);
        $id_cliente      = (int) ($_POST['id_cliente']      ?? 0);
        $total_a_cancelar = $_POST['total_a_cancelar'] ?? '';

        if ($nro_venta <= 0 || $id_cliente <= 0 || $total_a_cancelar === '') {
            $this->flash('Todos los campos son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        if (!is_numeric($total_a_cancelar) || (float) $total_a_cancelar <= 0) {
            $this->flash('El monto total debe ser un valor numérico mayor a cero.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        $cartItemModel = new CartItem();
        if ($cartItemModel->countByNroVenta($nro_venta) === 0) {
            $this->flash('El carrito está vacío. Agrega productos antes de registrar la venta.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        $saleModel = new Sale();
        $ok = $saleModel->storeWithStock([
            'nro_venta'    => $nro_venta,
            'id_cliente'   => $id_cliente,
            'total_pagado' => (float) $total_a_cancelar,
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
        $id = $id ?? (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Venta inválida.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $saleModel = new Sale();
        $sale      = $saleModel->findWithDetails($id);

        if (!$sale) {
            $this->flash('No se encontró la venta solicitada.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $this->renderWithLayout('views/sales/show.php', array_merge(
            $this->sessionData(),
            [
                'id_venta'         => (int) $sale['id_venta'],
                'nro_venta'        => $sale['nro_venta'],
                'total_pagado'     => $sale['total_pagado'],
                'fyh_creacion'     => $sale['fyh_creacion'],
                'fyh_actualizacion'=> $sale['fyh_actualizacion'],
                'nombre_cliente'   => $sale['nombre_cliente'],
                'nit_ci_cliente'   => $sale['nit_ci_cliente'],
                'celular_cliente'  => $sale['celular_cliente'],
                'email_cliente'    => $sale['email_cliente'],
                'items'            => $sale['items'],
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
        $id = $id ?? (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Venta inválida.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $saleModel = new Sale();
        $sale      = $saleModel->findWithDetails($id);

        if (!$sale) {
            $this->flash('No se encontró la venta solicitada.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $this->renderWithLayout('views/sales/delete.php', array_merge(
            $this->sessionData(),
            [
                'id_venta'         => (int) $sale['id_venta'],
                'nro_venta'        => $sale['nro_venta'],
                'total_pagado'     => $sale['total_pagado'],
                'fyh_creacion'     => $sale['fyh_creacion'],
                'nombre_cliente'   => $sale['nombre_cliente'],
                'nit_ci_cliente'   => $sale['nit_ci_cliente'],
                'items'            => $sale['items'],
                'csrf_token'       => Auth::generateCsrfToken(),
            ]
        ));
    }

    /**
     * Genera la factura PDF de una venta usando TCPDF.
     * No usa renderWithLayout() — emite el PDF directamente inline.
     *
     * @param int|null $id ID de la venta.
     */
    public function invoice(?int $id = null): void
    {
        $id = $id ?? (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Venta inválida.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $saleModel = new Sale();
        $sale      = $saleModel->findWithDetails($id);

        if (!$sale) {
            $this->flash('No se encontró la venta solicitada.', 'error');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $user         = Auth::user();
        $nombres_sesion = $user['nombres'] ?? '';

        $nombre_cliente = $sale['nombre_cliente'];
        $nit_ci_cliente = $sale['nit_ci_cliente'];
        $total_pagado   = (float) $sale['total_pagado'];
        $fecha          = date('d/m/Y', strtotime($sale['fyh_creacion']));
        $items          = $sale['items'];


        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, [215, 279], true, 'UTF-8', false);
        $pdf->setCreator(PDF_CREATOR);
        $pdf->setAuthor('Sistema de Ventas');
        $pdf->setTitle('Factura de venta');
        $pdf->setSubject('Factura de venta');
        $pdf->setKeywords('Factura de venta');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->setMargins(15, 15, 15);
        $pdf->setAutoPageBreak(true, 5);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setFont('Helvetica', '', 12);
        $pdf->AddPage();

        $logoPath = __DIR__ . '/../../public/img/Logo.jpg';

        $html = '<table border="0" style="font-size: 10px">
    <tr>
        <td style="text-align: center; width: 180px">
        <img src="' . $logoPath . '" alt="Logo" width="70px"><br><br>
            <b>SISTEMA DE VENTAS</b><br>
            Tercer anillo interno, Av. Bush<br>
            3113646 - 78569885<br>
            SANTA CRUZ - BOLIVIA
        </td>
        <td style="width: 179px"></td>
        <td style="font-size: 16px; width: 300px"><br><br><br><br>
            <b>Número factura: </b>' . $id . '<br>
            <b>Número de autorización: </b>565586565
            <p style="text-align: center"><b>ORIGINAL</b></p>
        </td>
    </tr>
</table>

<p style="text-align: center; font-size: 25px"><b>FACTURA</b></p>

<div>
    <table border="0" cellspacing="2">
        <tr>
            <td><b>Fecha: </b>' . $fecha . '</td>
            <td></td>
            <td><b>NIT/CI: </b>' . htmlspecialchars($nit_ci_cliente, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
        <tr>
            <td colspan="3"><b>Señor(a): </b>' . htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8') . '</td>
        </tr>
    </table>
</div>

<br>

<table border="1" cellpadding="5" style="font-size: 12px">
    <tr style="text-align: center; background-color: #d6d6d6">
        <th style="width: 40px"><b>Nro</b></th>
        <th style="width: 120px"><b>Producto</b></th>
        <th style="width: 260px"><b>Descripción</b></th>
        <th style="width: 65px"><b>Cantidad</b></th>
        <th style="width: 100px"><b>Precio Unitario</b></th>
        <th style="width: 74px"><b>Sub Total</b></th>
    </tr>
';

        $contador       = 0;
        $precio_total   = 0.0;
        $cantidad_total = 0;
        $total_unitarios = 0.0;

        foreach ($items as $item) {
            $contador++;
            $cantidad_item   = (int) $item['cantidad'];
            $precio_unitario = (float) $item['precio_venta'];
            $sub_total       = $cantidad_item * $precio_unitario;
            $precio_total   += $sub_total;
            $cantidad_total += $cantidad_item;
            $total_unitarios += $precio_unitario;

            $html .= '<tr>
        <td style="text-align: center">' . $contador . '</td>
        <td>' . htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') . '</td>
        <td>' . htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') . '</td>
        <td style="text-align: center">' . $cantidad_item . '</td>
        <td style="text-align: center">Bs. ' . number_format($precio_unitario, 2) . '</td>
        <td style="text-align: center">Bs. ' . number_format($sub_total, 2) . '</td>
    </tr>';
        }

        $monto_literal = NumberToWords::convert($precio_total);

        $html .= '<tr style="background-color: #d6d6d6">
        <td colspan="3" style="text-align: right"><b>Total</b></td>
        <td style="text-align: center">' . $cantidad_total . '</td>
        <td style="text-align: center">Bs. ' . number_format($total_unitarios, 2) . '</td>
        <td style="text-align: center">Bs. ' . number_format($precio_total, 2) . '</td>
    </tr>
</table>

<p style="text-align: right"><b>Monto Total: </b>Bs. ' . number_format($precio_total, 2) . '</p>
<p><b>Son: </b>' . $monto_literal . '</p>
<br>
======================================<br>
<b>Usuario: </b>' . htmlspecialchars($nombres_sesion, ENT_QUOTES, 'UTF-8') . '
<p style="text-align: center"></p>
<p style="text-align: center">Esta factura contribuye al desarrollo del país, el uso ilícito de ésta será sancionado de acuerdo a la ley</p>
<p style="text-align: center">Gracias por su preferencia</p>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->RoundedRect(15, 80, 186, 15, 3.50, '1111', 'D');

        $style = [
            'border'        => 0,
            'vpadding'      => '3',
            'hpadding'      => '3',
            'fgcolor'       => [0, 0, 0],
            'bgcolor'       => false,
            'module_width'  => 1,
            'module_height' => 1,
        ];

        $qrData = 'Factura del sistema de ventas, cliente: ' . $nombre_cliente
            . ', NIT/CI: ' . $nit_ci_cliente
            . ', fecha: ' . $fecha
            . ', monto: ' . number_format($precio_total, 2);
        $pdf->write2DBarcode($qrData, 'QRCODE,L', 170, 240, 35, 35, $style);

        $pdf->Output('factura_venta_' . $id . '.pdf', 'I');
    }

    /**
     * Elimina una venta, revirtiendo el stock de todos los productos del carrito.
     */
    public function destroy(): void
    {
        $this->validateCsrfOrFail();

        $id_venta = (int) ($_POST['id_venta'] ?? 0);

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

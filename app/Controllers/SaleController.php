<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\InvoicePdf;
use App\Helpers\Money;
use App\Models\ActivityLog;
use App\Models\CartItem;
use App\Models\Client;
use App\Models\PaymentMethod;
use App\Models\PosReservation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;

class SaleController extends Controller
{
    /**
     * Listado de ventas con datos del cliente.
     */
    public function index(): void
    {
        $saleModel = new Sale();
        $userId = Auth::can('view_sales_all') ? null : (int)Auth::user()['id_usuario'];
        $sales_data = $saleModel->allWithDetails($userId);

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

        $nro_venta = (new PosReservation())->openFor((int)Auth::user()['id_usuario']);

        $cart_items = $saleModel->withSubtotals($cartItemModel->getByNroVenta($nro_venta));
        $totals = $saleModel->computeInvoiceTotals($cart_items);

        $this->renderWithLayout('views/sales/create.php', array_merge(
            $this->sessionData(),
            [
                'nro_venta' => $nro_venta,
                'cart_items' => $cart_items,
                'cantidad_total' => $totals['cantidad_total'],
                'precio_total' => $totals['precio_total'],
                'products' => $productModel->allWithCategories(),
                'clients' => $clientModel->all(),
                'metodos_activos' => (new PaymentMethod())->active(),
                'csrf_token' => Auth::generateCsrfToken(),
                'pageStyles' => ['/css/modules/sales/create.css'],
                'pageScripts' => ['/js/modules/sales/sales-create.js'],
            ]
        ), true, ['datatable', 'validation']);
    }

    /**
     * Cancela la venta en curso: elimina el carrito activo y redirige al listado.
     */
    public function cancel(): void
    {
        $this->validateCsrfOrFail();

        $nro_venta = (int)($_POST['nro_venta'] ?? 0);

        if ($nro_venta > 0) {
            $result = (new CartItem())->clearCart($nro_venta, (int)Auth::user()['id_usuario']);
            if (!$result['ok']) {
                $this->forbidden();
            }
            (new PosReservation())->release($nro_venta);
        }

        $this->redirect(BASE_URL . '/sales');
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

        $result = (new CartItem())->addItem($nro_venta, $id_producto, $cantidad, (int)Auth::user()['id_usuario']);

        if (in_array($result['error'], ['forbidden', 'finalized'], true)) {
            $this->forbidden();
        }
        if (!$result['ok']) {
            $this->flash('Stock insuficiente para la cantidad solicitada.', 'warning');
        } elseif ($result['error'] === 'stock_warning') {
            $this->flash('Atención: la cantidad solicitada supera el stock disponible. Se agregó al carrito y el saldo quedará negativo si confirma la venta.', 'warning');
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

        $result = (new CartItem())->removeItem($id_carrito, (int)Auth::user()['id_usuario']);
        if (!$result['ok']) {
            $this->forbidden();
        }

        $this->redirect(BASE_URL . '/sales/create');
    }

    /**
     * Finaliza la venta: inserta en tb_ventas, sus líneas de pago y decrementa stock.
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $nro_venta = (int)($_POST['nro_venta'] ?? 0);
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);

        if ($nro_venta <= 0) {
            $this->flash('Todos los campos son obligatorios.', 'error');
            $this->redirect(BASE_URL . '/sales/create');
            return;
        }

        // Líneas de pago desde el POST: solo montos > 0 (pagos[id][monto]).
        $payments = [];
        $rawPagos = $_POST['pagos'] ?? [];
        if (is_array($rawPagos)) {
            foreach ($rawPagos as $idMetodo => $line) {
                if (!is_array($line)) {
                    continue;
                }
                // El monto viaja como texto: el modelo lo convierte a céntimos y rechaza lo no numérico.
                // Solo se descartan las líneas vacías o en cero (métodos del modo mixto sin usar).
                $monto = trim((string)($line['monto'] ?? ''));
                if ($monto === '' || Money::toCents($monto) === 0) {
                    continue;
                }
                $payments[] = [
                    'id_metodo_pago' => (int)$idMetodo,
                    'monto'          => $monto,
                ];
            }
        }

        $saleModel = new Sale();
        $result = $saleModel->storeWithStock([
            'nro_venta'  => $nro_venta,
            'id_cliente' => $id_cliente > 0 ? $id_cliente : null,
            'tipo_documento' => $this->saleDocumentType($_POST['tipo_documento'] ?? ''),
        ], $payments, (int)Auth::user()['id_usuario']);

        if (($result['error'] ?? null) === 'forbidden') {
            $this->forbidden();
        }

        if ($result['ok'] && $result['replayed']) {
            $sale = $saleModel->findWithDetails((int)$result['id_venta']);
            $stockWarning = (bool)array_filter(
                $sale['items'] ?? [],
                static fn(array $item): bool => (float)($item['stock'] ?? 0) < 0
            );
            $this->flash($this->saleSuccessMessage($result, $stockWarning), $stockWarning ? 'warning' : 'success');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        if ($result['ok']) {
            $idVenta = (int)$result['id_venta'];
            $sale = $saleModel->findWithDetails($idVenta);
            $stockWarning = (bool)array_filter(
                $sale['items'] ?? [],
                static fn(array $item): bool => (float)($item['stock'] ?? 0) < 0
            );
            $totalPagado = (float)($sale['total_pagado'] ?? 0);
            $cliente = $id_cliente > 0 ? (new Client())->find($id_cliente) : null;
            ActivityLog::record(
                'create',
                'sale',
                $idVenta,
                "Venta Nro {$nro_venta} registrada (total " . APP_CURRENCY_SYMBOL . " " . number_format($totalPagado, 2) . ")",
                null,
                [
                    'nro_venta'      => $nro_venta,
                    'nombre_cliente' => $cliente['nombre_cliente'] ?? 'Consumidor final',
                    'total_pagado'   => $totalPagado,
                ]
            );
            $this->flash($this->saleSuccessMessage($result, $stockWarning), $stockWarning ? 'warning' : 'success');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $this->flash($this->paymentErrorMessage($result), 'error');
        $this->redirect(BASE_URL . '/sales/create');
    }

    /**
     * Mensaje de éxito del cobro (el reenvío de un cobro ya registrado muestra el mismo).
     *
     * @param array $result Retorno de Sale::storeWithStock().
     */
    private function saleSuccessMessage(array $result, bool $stockWarning = false): string
    {
        $mensaje = 'La venta se registró exitosamente.';
        if ((float)($result['vuelto'] ?? 0) > 0) {
            $mensaje .= ' Vuelto: ' . APP_CURRENCY_SYMBOL . ' ' . number_format((float)$result['vuelto'], 2);
        }
        if ($stockWarning) {
            $mensaje .= ' Atención: uno o más productos quedaron con stock negativo.';
        }

        return $mensaje;
    }

    private function saleDocumentType(mixed $value): string
    {
        $allowed = ['boleta', 'factura', 'guia', 'credito', 'otro'];

        return is_string($value) && in_array($value, $allowed, true) ? $value : '';
    }

    /**
     * Traduce el código de error de storeWithStock() a un mensaje de usuario en español.
     *
     * @param array $result Retorno de Sale::storeWithStock().
     * @return string Mensaje listo para flash().
     */
    private function paymentErrorMessage(array $result): string
    {
        $error = (string)($result['error'] ?? 'generic');

        if ($error === 'faltante') {
            $faltante = number_format((float)($result['faltante'] ?? 0), 2);
            return 'La suma de pagos es menor al total. Faltan ' . APP_CURRENCY_SYMBOL . " {$faltante}.";
        }

        $messages = [
            'empty_cart'         => 'El carrito está vacío. Agrega productos antes de registrar la venta.',
            'sin_metodos'        => 'No hay métodos de pago activos. Configure al menos un método para poder cobrar.',
            'invalid_payment'    => 'Pago inválido: verifique los montos y los métodos seleccionados.',
            'exceso_sin_efectivo' => 'El exceso sobre el total solo se admite en efectivo.',
            'vuelto_excede_efectivo' => 'El vuelto no puede ser mayor al efectivo recibido.',
            'invalid_document_type' => 'Seleccione un tipo de documento interno válido.',
            'total_cero'         => 'El total de la venta es cero: no se puede cobrar.',
            'total_excede'       => 'El total de la venta supera el máximo permitido (' . APP_CURRENCY_SYMBOL . ' 99,999,999.99).',
            'generic'            => 'Error al registrar la venta. Intente nuevamente.',
        ];

        return $messages[$error] ?? $messages['generic'];
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

        if (!Auth::can('view_sales_all') && (int)$sale['id_usuario'] !== (int)Auth::user()['id_usuario']) {
            $this->forbidden('No tienes permiso para ver esta venta.');
        }

        $items = $saleModel->withSubtotals($sale['items']);
        $totals = $saleModel->computeInvoiceTotals($items, (float)$sale['total_pagado']);

        $returnModel = new SaleReturn();
        $devoluciones = $returnModel->byVenta($id);

        $this->renderWithLayout('views/sales/show.php', array_merge(
            $this->sessionData(),
            [
                'id_venta' => (int)$sale['id_venta'],
                'nro_venta' => $sale['nro_venta'],
                'tipo_documento' => $sale['tipo_documento'],
                'tipo_documento_label' => Sale::documentTypeLabel((string)$sale['tipo_documento']),
                'total_pagado' => $sale['total_pagado'],
                'fyh_creacion' => $sale['fyh_creacion'],
                'fyh_actualizacion' => $sale['fyh_actualizacion'],
                'nombre_cliente' => $sale['nombre_cliente'],
                'nit_ci_cliente' => $sale['nit_ci_cliente'],
                'celular_cliente' => $sale['celular_cliente'],
                'email_cliente' => $sale['email_cliente'],
                'items' => $items,
                'total_productos' => count($items),
                'cantidad_acum' => $totals['cantidad_total'],
                'subtotal_acum' => $totals['total_pagado'],
                'ajuste' => $totals['ajuste'],
                'pagos' => $sale['payments'] ?? [],
                'vuelto' => $sale['vuelto'] ?? 0.0,
                'devoluciones' => $devoluciones,
            ]
        ));
    }

    /**
     * Muestra la página de confirmación de eliminación de una venta.
     *
     * @param int|null $id ID de la venta.
     */
    public function delete(?int $id = null): void
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

        if (!Auth::can('view_sales_all') && (int)$sale['id_usuario'] !== (int)Auth::user()['id_usuario']) {
            $this->forbidden('No tienes permiso para eliminar esta venta.');
        }

        $items = $saleModel->withSubtotals($sale['items']);
        $totals = $saleModel->computeInvoiceTotals($items, (float)$sale['total_pagado']);

        $this->renderWithLayout('views/sales/delete.php', array_merge(
            $this->sessionData(),
            [
                'id_venta' => (int)$sale['id_venta'],
                'nro_venta' => $sale['nro_venta'],
                'total_pagado' => $sale['total_pagado'],
                'fyh_creacion' => $sale['fyh_creacion'],
                'nombre_cliente' => $sale['nombre_cliente'],
                'nit_ci_cliente' => $sale['nit_ci_cliente'],
                'items' => $items,
                'total_productos' => count($items),
                'cantidad_acum' => $totals['cantidad_total'],
                'subtotal_acum' => $totals['total_pagado'],
                'ajuste' => $totals['ajuste'],
                'csrf_token' => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/sales/sales-delete.js'],
            ]
        ));
    }

    /**
     * Genera el comprobante interno PDF de una venta usando TCPDF.
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

        if (!Auth::can('view_sales_all') && (int)$sale['id_usuario'] !== (int)Auth::user()['id_usuario']) {
            $this->forbidden('No tienes permiso para ver este comprobante.');
        }

        $totals = $saleModel->computeInvoiceTotals($sale['items'], (float)$sale['total_pagado']);
        // El vendedor que consta en la factura es el de la venta, no quien la imprime.
        $vendedor = $sale['nombre_vendedor'] ?? '—';

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
        $snapshot = $saleModel->findWithDetails($id_venta);

        if ($snapshot && !Auth::can('view_sales_all') && (int)$snapshot['id_usuario'] !== (int)Auth::user()['id_usuario']) {
            $this->forbidden('No tienes permiso para eliminar esta venta.');
        }

        $result = $saleModel->destroyWithStock($id_venta);

        if ($result['error'] === 'has_returns') {
            $this->flash(
                'No se puede eliminar la venta porque tiene devoluciones asociadas.',
                'warning'
            );
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        if ($result['ok']) {
            if ($snapshot) {
                ActivityLog::record(
                    'delete',
                    'sale',
                    $id_venta,
                    "Venta Nro {$snapshot['nro_venta']} eliminada (total " . APP_CURRENCY_SYMBOL . " {$snapshot['total_pagado']}); stock restaurado.",
                    [
                        'nro_venta'      => $snapshot['nro_venta'],
                        'nombre_cliente' => $snapshot['nombre_cliente'] ?? 'Consumidor final',
                        'total_pagado'   => $snapshot['total_pagado'],
                        'fyh_creacion'   => $snapshot['fyh_creacion'],
                        'items'          => $snapshot['items'],
                    ]
                );
            }
            $this->flash('La venta se eliminó exitosamente y el stock fue restaurado.', 'success');
            $this->redirect(BASE_URL . '/sales');
            return;
        }

        $this->flash('Error al eliminar la venta.', 'error');
        $this->redirect(BASE_URL . '/sales');
    }
}

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Sale;
use App\Models\SaleReturn;

class SaleReturnController extends Controller
{
    /**
     * Listado de devoluciones (FR-15, FR-17).
     */
    public function index(): void
    {
        $returnModel = new SaleReturn();
        $userId = Auth::can('view_sales_all') ? null : (int)Auth::user()['id_usuario'];
        $returns = $returnModel->allWithDetails($userId);

        $this->renderWithLayout('views/returns/index.php', array_merge(
            $this->sessionData(),
            [
                'returns' => $returns,
                'pageScripts' => ['/js/modules/returns/returns-index.js'],
                'pageStyles' => ['/css/modules/returns/returns.css'],
            ]
        ), true, ['datatable']);
    }

    /**
     * Formulario de creación de devolución (FR-1, FR-14, FR-15).
     *
     * Con ?sale_id=N carga la venta y muestra los ítems pendientes.
     * Sin sale_id muestra un selector de ventas con datos server-side.
     */
    public function create(): void
    {
        $saleId = (int)($_GET['sale_id'] ?? 0);

        if ($saleId <= 0) {
            $saleModel = new Sale();
            $userId = Auth::can('view_sales_all') ? null : (int)Auth::user()['id_usuario'];
            $sales = $saleModel->allWithDetails($userId);

            $this->renderWithLayout('views/returns/select-sale.php', array_merge(
                $this->sessionData(),
                [
                    'sales' => $sales,
                    'pageScripts' => ['/js/modules/returns/returns-select-sale.js'],
                    'pageStyles' => ['/css/modules/returns/returns.css'],
                ]
            ), true, ['datatable']);
            return;
        }

        $saleModel = new Sale();
        $sale = $saleModel->findWithDetails($saleId);

        if (!$sale) {
            $this->flash('No se encontró la venta solicitada.', 'error');
            $this->redirect(BASE_URL . '/returns');
            return;
        }

        $this->checkSaleScope($sale);

        $returnModel = new SaleReturn();
        $pendingItems = $returnModel->pendingByVenta($saleId);

        $this->renderWithLayout('views/returns/create.php', array_merge(
            $this->sessionData(),
            [
                'id_venta' => (int)$sale['id_venta'],
                'nro_venta' => $sale['nro_venta'],
                'nombre_cliente' => $sale['nombre_cliente'],
                'fyh_creacion' => $sale['fyh_creacion'],
                'id_usuario' => $sale['id_usuario'],
                'items' => $pendingItems,
                'csrf_token' => Auth::generateCsrfToken(),
                'pageScripts' => ['/js/modules/returns/returns-create.js'],
                'pageStyles' => ['/css/modules/returns/returns.css'],
            ]
        ));
    }

    /**
     * Registra una devolución (FR-1, FR-2, FR-7, FR-8, FR-9).
     */
    public function store(): void
    {
        $this->validateCsrfOrFail();

        $idVenta = (int)($_POST['id_venta'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        if ($idVenta <= 0) {
            $this->flash('Venta inválida.', 'error');
            $this->redirect(BASE_URL . '/returns');
            return;
        }

        if ($motivo === '') {
            $this->flash('El motivo es obligatorio.', 'error');
            $this->redirect(BASE_URL . '/returns/create?sale_id=' . $idVenta);
            return;
        }

        if (mb_strlen($motivo) < 10) {
            $this->flash('El motivo debe tener al menos 10 caracteres.', 'error');
            $this->redirect(BASE_URL . '/returns/create?sale_id=' . $idVenta);
            return;
        }

        $saleModel = new Sale();
        $sale = $saleModel->findWithDetails($idVenta);

        if (!$sale) {
            $this->flash('No se encontró la venta solicitada.', 'error');
            $this->redirect(BASE_URL . '/returns');
            return;
        }

        $this->checkSaleScope($sale);

        $quantitiesByProduct = [];
        foreach ($_POST as $key => $value) {
            if (str_starts_with($key, 'qty_')) {
                $productId = (int)substr($key, 4);
                $quantity = (int)$value;
                if ($productId > 0 && $quantity >= 0) {
                    $quantitiesByProduct[$productId] = $quantity;
                }
            }
        }

        $returnModel = new SaleReturn();
        $result = $returnModel->register($idVenta, $motivo, $quantitiesByProduct);

        if ($result['ok']) {
            $this->flash(
                "Devolución Nro {$result['nro']} registrada (monto: " . APP_CURRENCY_SYMBOL . " " . number_format($result['monto'], 2) . ").",
                'success'
            );
            $this->redirect(BASE_URL . '/returns/show/' . $result['id']);
            return;
        }

        $errorMessage = match ($result['error'] ?? '') {
            'not_found' => 'No se encontró la venta.',
            'empty'     => 'No hay ítems para devolver.',
            'conflict'  => 'La cantidad excede el pendiente de devolución para algún ítem.',
            default     => 'Error al registrar la devolución. Intente nuevamente.',
        };
        $this->flash($errorMessage, 'error');
        $this->redirect(BASE_URL . '/returns/create?sale_id=' . $idVenta);
    }

    /**
     * Detalle de una devolución (FR-17, FR-15).
     *
     * @param int|null $id ID de la devolución.
     */
    public function show(?int $id = null): void
    {
        $id = $id ?? (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->flash('Devolución inválida.', 'error');
            $this->redirect(BASE_URL . '/returns');
            return;
        }

        $returnModel = new SaleReturn();
        $return = $returnModel->findWithDetails($id);

        if (!$return) {
            $this->flash('No se encontró la devolución solicitada.', 'error');
            $this->redirect(BASE_URL . '/returns');
            return;
        }

        $this->checkSaleScope([
            'id_usuario' => $return['id_usuario_venta'],
        ]);

        $this->renderWithLayout('views/returns/show.php', array_merge(
            $this->sessionData(),
            [
                'return' => $return,
                'pageStyles' => ['/css/modules/returns/returns.css'],
            ]
        ));
    }

    /**
     * Verifica el scoping FR-15 sobre una venta.
     * Si el usuario no tiene view_sales_all y la venta no es suya → forbidden.
     * Si id_usuario de la venta es NULL → solo view_sales_all puede acceder.
     */
    private function checkSaleScope(array $sale): void
    {
        if (Auth::can('view_sales_all')) {
            return;
        }

        $saleUserId = $sale['id_usuario'] !== null ? (int)$sale['id_usuario'] : null;
        $currentUserId = (int)Auth::user()['id_usuario'];

        if ($saleUserId === null || $saleUserId !== $currentUserId) {
            $this->forbidden('No tienes permiso para acceder a devoluciones de esta venta.');
        }
    }
}

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;

class InventoryController extends Controller
{
    /**
     * Muestra el módulo de inventario con dos pestañas: stock y ajustes.
     */
    public function index(): void
    {
        $tabWhitelist = ['stock', 'ajustes'];
        $activeTab = in_array($_GET['tab'] ?? '', $tabWhitelist, true) ? $_GET['tab'] : 'stock';

        if ($activeTab === 'stock') {
            $productos      = (new Product())->inventoryList('todos');
            $ultimosAjustes = (new StockAdjustment())->history([], 10);
            $todosProductos = (new Product())->all();

            $this->renderWithLayout(
                'views/inventory/index.php',
                array_merge(
                    $this->sessionData(),
                    [
                        'activeTab'      => $activeTab,
                        'productos'      => $productos,
                        'ultimosAjustes' => $ultimosAjustes,
                        'todosProductos' => $todosProductos,
                        'csrf_token'     => Auth::generateCsrfToken(),
                        'pageScripts'    => ['/js/modules/inventory/inventory.js'],
                    ]
                ),
                true,
                ['datatable', 'select2']
            );
            return;
        }

        // Tab: ajustes
        $tipoWhitelist = ['', 'entrada', 'salida'];
        $filtros = [
            'desde'       => $_GET['desde'] ?? '',
            'hasta'       => $_GET['hasta'] ?? '',
            'tipo'        => in_array($_GET['tipo'] ?? '', $tipoWhitelist, true) ? ($_GET['tipo'] ?? '') : '',
            'id_producto' => isset($_GET['id_producto']) && $_GET['id_producto'] !== '' ? (int)$_GET['id_producto'] : 0,
        ];

        $filters = array_filter($filtros, fn($v) => $v !== '' && $v !== 0 && $v !== false && $v !== null);

        $ajustes        = (new StockAdjustment())->history($filters);
        $todosProductos = (new Product())->all();

        $this->renderWithLayout(
            'views/inventory/index.php',
            array_merge(
                $this->sessionData(),
                [
                    'activeTab'      => $activeTab,
                    'ajustes'        => $ajustes,
                    'todosProductos' => $todosProductos,
                    'filtros'        => $filtros,
                    'csrf_token'     => Auth::generateCsrfToken(),
                    'pageScripts'    => ['/js/modules/inventory/inventory.js'],
                ]
            ),
            true,
            ['datatable', 'select2']
        );
    }

    /**
     * Procesa el formulario de ajuste de stock.
     */
    public function storeAdjustment(): void
    {
        $this->validateCsrfOrFail();

        $tipo       = $_POST['tipo'] ?? '';
        $cantidad   = (int)($_POST['cantidad'] ?? 0);
        $motivo     = trim($_POST['motivo'] ?? '');
        $id_producto = (int)($_POST['id_producto'] ?? 0);

        if (!in_array($tipo, ['entrada', 'salida'], true)) {
            $this->flash('Tipo de ajuste inválido.', 'error');
            $this->redirect(BASE_URL . '/inventory?tab=ajustes');
            return;
        }

        if ($cantidad <= 0) {
            $this->flash('La cantidad debe ser mayor a cero.', 'error');
            $this->redirect(BASE_URL . '/inventory?tab=ajustes');
            return;
        }

        if ($motivo === '') {
            $this->flash('El motivo es obligatorio.', 'error');
            $this->redirect(BASE_URL . '/inventory?tab=ajustes');
            return;
        }

        if (mb_strlen($motivo) > 255) {
            $this->flash('El motivo no puede exceder los 255 caracteres.', 'error');
            $this->redirect(BASE_URL . '/inventory?tab=ajustes');
            return;
        }

        if ($id_producto <= 0) {
            $this->flash('Debe seleccionar un producto válido.', 'error');
            $this->redirect(BASE_URL . '/inventory?tab=ajustes');
            return;
        }

        $producto = (new Product())->find($id_producto);
        if (!$producto) {
            $this->flash('El producto seleccionado no existe.', 'error');
            $this->redirect(BASE_URL . '/inventory?tab=ajustes');
            return;
        }

        $result = (new StockAdjustment())->register($id_producto, $tipo, $cantidad, $motivo);

        if ($result['ok']) {
            $this->flash('Ajuste registrado exitosamente.', 'success');
        } else {
            $this->flash($result['error'], 'error');
        }

        $this->redirect(BASE_URL . '/inventory?tab=ajustes');
    }
}

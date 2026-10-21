<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Product;
use App\Models\InventoryReport;
use App\Models\StockAdjustment;

class InventoryController extends Controller
{
    /**
     * Muestra el módulo de inventario con dos pestañas: stock y ajustes.
     */
    public function index(): void
    {
        $tabWhitelist = ['stock', 'ajustes', 'movimientos'];
        $activeTab = in_array($_GET['tab'] ?? '', $tabWhitelist, true) ? $_GET['tab'] : 'stock';

        if ($activeTab === 'stock') {
            $productModel   = new Product();
            $productos      = $productModel->withStockBars($productModel->inventoryList('todos'));
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
                        'adjustment_token' => Auth::issueFormToken('stock_adjustment'),
                        'pageScripts'    => ['/js/modules/inventory/inventory.js'],
                    ]
                ),
                true,
                ['datatable', 'select2']
            );
            return;
        }

        if ($activeTab === 'movimientos') {
            $today = date('Y-m-d');
            $filters = [
                'desde' => $this->validDate($_GET['desde'] ?? '') ?? date('Y-m-01'),
                'hasta' => $this->validDate($_GET['hasta'] ?? '') ?? $today,
                'id_producto' => isset($_GET['id_producto']) && ctype_digit((string)$_GET['id_producto'])
                    ? (int)$_GET['id_producto']
                    : 0,
            ];

            if ($filters['desde'] > $filters['hasta']) {
                $filters['desde'] = $filters['hasta'];
            }

            $report = new InventoryReport();
            $this->renderWithLayout(
                'views/inventory/index.php',
                array_merge(
                    $this->sessionData(),
                    [
                        'activeTab' => $activeTab,
                        'filtros' => $filters,
                        'resumenMovimientos' => $report->summary($filters),
                        'movimientos' => $report->movements($filters),
                        'todosProductos' => (new Product())->all(),
                        'pageScripts' => ['/js/modules/inventory/inventory.js'],
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
                    'adjustment_token' => Auth::issueFormToken('stock_adjustment'),
                    'pageScripts'    => ['/js/modules/inventory/inventory.js'],
                ]
            ),
            true,
            ['datatable', 'select2']
        );
    }

    public function exportMovements(): void
    {
        $from = $this->validDate($_GET['desde'] ?? '') ?? date('Y-m-01');
        $to = $this->validDate($_GET['hasta'] ?? '') ?? date('Y-m-d');
        $productId = isset($_GET['id_producto']) && ctype_digit((string)$_GET['id_producto'])
            ? (int)$_GET['id_producto']
            : 0;

        if ($from > $to) {
            $this->flash('El período del informe no es válido.', 'error');
            $this->redirect(BASE_URL . '/inventory?tab=movimientos');
        }

        $rows = (new InventoryReport())->movements([
            'desde' => $from,
            'hasta' => $to,
            'id_producto' => $productId,
        ]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="movimientos-inventario-' . $from . '-' . $to . '.csv"');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new \RuntimeException('No se pudo generar el archivo de movimientos.');
        }
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Fecha', 'Código', 'Producto', 'Unidad', 'Tipo', 'Cantidad', 'Referencia', 'Usuario'], ';', '"', '\\');
        foreach ($rows as $row) {
            $values = [
                $row['fecha'],
                $row['codigo'],
                $row['producto'],
                $row['unidad_medida'],
                $row['tipo'],
                $row['cantidad'],
                $row['referencia'],
                $row['usuario'],
            ];
            fputcsv($output, array_map([$this, 'csvSafeValue'], $values), ';', '"', '\\');
        }
        fclose($output);
        exit;
    }

    public function exportSummary(): void
    {
        $from = $this->validDate($_GET['desde'] ?? '') ?? date('Y-m-01');
        $to = $this->validDate($_GET['hasta'] ?? '') ?? date('Y-m-d');
        $productId = isset($_GET['id_producto']) && ctype_digit((string)$_GET['id_producto'])
            ? (int)$_GET['id_producto']
            : 0;

        if ($from > $to) {
            $this->flash('El período del informe no es válido.', 'error');
            $this->redirect(BASE_URL . '/inventory?tab=movimientos');
        }

        $rows = (new InventoryReport())->summary([
            'desde' => $from,
            'hasta' => $to,
            'id_producto' => $productId,
        ]);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="resumen-inventario-' . $from . '-' . $to . '.csv"');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new \RuntimeException('No se pudo generar el resumen de inventario.');
        }
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Código', 'Producto', 'Unidad', 'Stock inicial', 'Ingresos', 'Salidas', 'Stock actual'], ';', '"', '\\');
        foreach ($rows as $row) {
            fputcsv($output, array_map([$this, 'csvSafeValue'], [
                $row['codigo'],
                $row['nombre'],
                $row['unidad_medida'],
                $row['stock_inicial'],
                $row['ingresos'],
                $row['salidas'],
                $row['stock_actual'],
            ]), ';', '"', '\\');
        }
        fclose($output);
        exit;
    }

    private function validDate(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $date = \DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function csvSafeValue(mixed $value): string
    {
        $value = (string)$value;

        return $value !== '' && !is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value) === 1
            ? "'" . $value
            : $value;
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

        // Se consume justo antes de escribir: un doble envío o un reenvío del formulario no repite el ajuste.
        if (!Auth::consumeFormToken('stock_adjustment', (string)($_POST['form_token'] ?? ''))) {
            $this->flash('Este ajuste ya fue registrado o el formulario venció. Revisa el historial antes de repetirlo.', 'warning');
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

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\ReportFilters;
use App\Helpers\ReportPdf;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Report;

class ReportController extends Controller
{
    /**
     * Devuelve el ID con el que acotar un reporte: null cuando el rol tiene el
     * permiso `_all` (ve todos los registros) o el ID propio en caso contrario.
     * Sin sesión devuelve 0, que no matchea ningún registro (fail-closed).
     */
    private function scopeUserId(string $allPermission): ?int
    {
        if (Auth::can($allPermission)) {
            return null;
        }
        $user = Auth::user();
        return (int)($user['id_usuario'] ?? 0);
    }

    public function index(): void
    {
        $session = $this->sessionData();
        $filters = ReportFilters::parseDateRange([]);
        $report  = new Report();

        $salesSummary     = Auth::can('view_sales_report') ? $report->salesSummary($filters['fecha_desde'], $filters['fecha_hasta'], $this->scopeUserId('view_sales_all')) : null;
        $purchaseSummary  = Auth::can('view_purchases_report') ? $report->purchasesTotals($filters['fecha_desde'], $filters['fecha_hasta'], $this->scopeUserId('view_purchases_all')) : null;
        $topProductos     = Auth::can('view_top_products_report') ? $report->topProducts($filters['fecha_desde'], $filters['fecha_hasta'], 5, 'cantidad', 0, $this->scopeUserId('view_sales_all')) : [];
        $clientesActivos  = Auth::can('view_clients_report') ? count($report->clientsByPeriod($filters['fecha_desde'], $filters['fecha_hasta'], $this->scopeUserId('view_sales_all'))) : null;

        // Ingresos y egresos solo se restan si ambos cubren el mismo alcance (todos los registros o
        // solo los propios); si no, el margen mezclaría universos distintos.
        $margen = null;
        if ($salesSummary !== null && $purchaseSummary !== null
            && Auth::can('view_sales_all') === Auth::can('view_purchases_all')) {
            $margen = (float)$salesSummary['total_ingresos'] - (float)$purchaseSummary['total_egresos'];
        }

        $this->renderWithLayout(
            'views/reports/index.php',
            array_merge($session, [
                'filters'         => $filters,
                'salesSummary'    => $salesSummary,
                'purchaseSummary' => $purchaseSummary,
                'topProductos'    => $topProductos,
                'clientesActivos' => $clientesActivos,
                'margen'          => $margen,
                'pageStyles'      => ['/css/modules/reports/reports.css'],
            ]),
            true,
            []
        );
    }

    public function sales(): void
    {
        $filters  = ReportFilters::parseDateRange($_GET);
        $report   = new Report();
        $session  = $this->sessionData();
        $userId   = $this->scopeUserId('view_sales_all');

        $rows   = $report->withNetAmounts($report->salesByPeriod($filters['fecha_desde'], $filters['fecha_hasta'], $userId));
        $totals = $report->salesTotals($filters['fecha_desde'], $filters['fecha_hasta'], $userId);
        $totals['devoluciones_periodos_anteriores'] = $report->returnsOutsideListed($filters['fecha_desde'], $filters['fecha_hasta'], $userId);

        $export = $this->exportFormat();
        if ($export !== '') {
            $this->exportSales($export, $filters, $rows, $totals);
            return;
        }

        $this->renderWithLayout(
            'views/reports/sales.php',
            array_merge($this->sessionData(), [
                'filters'     => $filters,
                'rows'        => $rows,
                'totals'      => $totals,
                'pageStyles'  => ['/css/modules/reports/reports.css'],
                'pageScripts' => ['/js/modules/reports/reports.js'],
            ]),
            true,
            ['datatable']
        );
    }

    public function purchases(): void
    {
        $filters = ReportFilters::parseDateRange($_GET);
        $report  = new Report();
        $userId  = $this->scopeUserId('view_purchases_all');

        $rows   = $report->purchasesByPeriod($filters['fecha_desde'], $filters['fecha_hasta'], $userId);
        $totals = $report->purchasesTotals($filters['fecha_desde'], $filters['fecha_hasta'], $userId);

        $export = $this->exportFormat();
        if ($export !== '') {
            $this->exportPurchases($export, $filters, $rows, $totals);
            return;
        }

        $this->renderWithLayout(
            'views/reports/purchases.php',
            array_merge($this->sessionData(), [
                'filters'     => $filters,
                'rows'        => $rows,
                'totals'      => $totals,
                'pageStyles'  => ['/css/modules/reports/reports.css'],
                'pageScripts' => ['/js/modules/reports/reports.js'],
            ]),
            true,
            ['datatable']
        );
    }

    public function topProducts(): void
    {
        $filters = ReportFilters::parseDateRange($_GET);
        $report  = new Report();

        $top       = ReportFilters::parseTop($_GET);
        $orden     = in_array($_GET['orden'] ?? '', ['cantidad', 'ingresos'], true) ? $_GET['orden'] : 'cantidad';
        $categoria = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int)$_GET['categoria'] : 0;

        $rows       = $report->topProducts($filters['fecha_desde'], $filters['fecha_hasta'], $top, $orden, $categoria, $this->scopeUserId('view_sales_all'));
        $categories = (new Category())->all();

        $export = $this->exportFormat();
        if ($export !== '') {
            $this->exportTopProducts($export, $filters, $rows);
            return;
        }

        $this->renderWithLayout(
            'views/reports/top-products.php',
            array_merge($this->sessionData(), [
                'filters'     => $filters,
                'rows'        => $rows,
                'categories'  => $categories,
                'top'         => $top,
                'orden'       => $orden,
                'categoria'   => $categoria,
                'pageStyles'  => ['/css/modules/reports/reports.css'],
                'pageScripts' => ['/js/modules/reports/reports.js'],
            ]),
            true,
            ['datatable', 'select2']
        );
    }

    public function clients(): void
    {
        $filters = ReportFilters::parseDateRange($_GET);
        $report  = new Report();

        $rows = $report->clientsByPeriod($filters['fecha_desde'], $filters['fecha_hasta'], $this->scopeUserId('view_sales_all'));

        $export = $this->exportFormat();
        if ($export !== '') {
            $this->exportClients($export, $filters, $rows);
            return;
        }

        $this->renderWithLayout(
            'views/reports/clients.php',
            array_merge($this->sessionData(), [
                'filters'       => $filters,
                'rows'          => $rows,
                'montoTotal'    => array_sum(array_column($rows, 'monto_acumulado')),
                'pageStyles'    => ['/css/modules/reports/reports.css'],
                'pageScripts'   => ['/js/modules/reports/reports.js'],
            ]),
            true,
            ['datatable']
        );
    }

    // ── Export helpers ────────────────────────────────────────────────────────

    /** Formato pedido en `?export=`; cualquier valor fuera de la lista se ignora y se muestra la página. */
    private function exportFormat(): string
    {
        $format = strtolower(trim((string)($_GET['export'] ?? '')));

        return in_array($format, ['pdf', 'csv', 'excel'], true) ? $format : '';
    }

    private function exportSales(string $format, array $filters, array $rows, array $totals): void
    {
        $currency = '(' . APP_CURRENCY_SYMBOL . ')';
        $headers  = ['N° Venta', 'Fecha', 'Cliente', "Total $currency", "Devuelto $currency", "Neto $currency"];
        $data = array_map(fn($r) => [
            $r['nro_venta'],
            date('d/m/Y H:i', strtotime($r['fyh_creacion'])),
            $r['cliente'] ?? 'Consumidor final',
            (float) $r['total_pagado'],
            (float) $r['monto_devuelto'],
            (float) $r['total_neto'],
        ], $rows);
        if ((float) $totals['devoluciones_periodos_anteriores'] > 0) {
            $data[] = ['', '', 'Devoluciones de ventas de períodos anteriores', '', '', -(float) $totals['devoluciones_periodos_anteriores']];
        }

        $subtitulo = 'Período: ' . date('d/m/Y', strtotime($filters['desde_display'])) . ' — ' . date('d/m/Y', strtotime($filters['hasta_display']));
        $totalRows = [
            'N° Ventas'    => (int) $totals['num_ventas'],
            'Total'        => (float) $totals['total_ingresos'],
            'Ticket Prom.' => (float) $totals['ticket_promedio'],
        ];

        $this->dispatchExport($format, 'Reporte de Ventas', $subtitulo, $headers, $data, $totalRows, 'reporte_ventas');
    }

    private function exportPurchases(string $format, array $filters, array $rows, array $totals): void
    {
        $headers = ['N° Compra', 'Fecha', 'Proveedor', 'Registrado por', 'Total (' . APP_CURRENCY_SYMBOL . ')'];
        $data = array_map(fn($r) => [
            $r['nro_compra'],
            date('d/m/Y', strtotime($r['fecha_compra'])),
            $r['proveedor'],
            $r['registrado_por'],
            (float) $r['monto_total'],
        ], $rows);

        $subtitulo = 'Período: ' . date('d/m/Y', strtotime($filters['desde_display'])) . ' — ' . date('d/m/Y', strtotime($filters['hasta_display']));
        $totalRows = [
            'N° Compras' => (int) $totals['num_compras'],
            'Total'      => (float) $totals['total_egresos'],
        ];

        $this->dispatchExport($format, 'Reporte de Compras', $subtitulo, $headers, $data, $totalRows, 'reporte_compras');
    }

    private function exportTopProducts(string $format, array $filters, array $rows): void
    {
        $headers = ['Producto', 'Categoría', 'Unidades Vendidas', 'Ingresos (' . APP_CURRENCY_SYMBOL . ')'];
        $data = array_map(fn($r) => [
            $r['nombre'],
            $r['categoria'] ?? '—',
            $r['unidades_vendidas'],
            (float) $r['ingresos'],
        ], $rows);

        $subtitulo = 'Período: ' . date('d/m/Y', strtotime($filters['desde_display'])) . ' — ' . date('d/m/Y', strtotime($filters['hasta_display']));

        $this->dispatchExport($format, 'Top Productos más Vendidos', $subtitulo, $headers, $data, [], 'reporte_top_productos');
    }

    private function exportClients(string $format, array $filters, array $rows): void
    {
        $headers = ['Cliente', 'NIT/CI', 'Email', 'N° Compras', 'Monto Acumulado (' . APP_CURRENCY_SYMBOL . ')', 'Última Compra'];
        $data = array_map(fn($r) => [
            $r['cliente'],
            $r['nit_ci'],
            $r['email'],
            $r['num_compras'],
            (float) $r['monto_acumulado'],
            $r['ultima_compra'] ? date('d/m/Y', strtotime($r['ultima_compra'])) : '—',
        ], $rows);

        $subtitulo = 'Período: ' . date('d/m/Y', strtotime($filters['desde_display'])) . ' — ' . date('d/m/Y', strtotime($filters['hasta_display']));

        $this->dispatchExport($format, 'Reporte de Clientes', $subtitulo, $headers, $data, [], 'reporte_clientes');
    }

    private function dispatchExport(
        string $format,
        string $titulo,
        string $subtitulo,
        array  $headers,
        array  $rows,
        array  $totals,
        string $filename
    ): void {
        $session = $this->sessionData();
        $emisor  = $session['nombres_sesion'];

        ActivityLog::record(
            'export',
            'report',
            null,
            "Exportó {$titulo} (" . strtoupper($format) . ')',
            null,
            ['formato' => $format, 'filas' => count($rows)]
        );

        $isPdf = $format === 'pdf';

        // Las celdas numéricas se pasan en bruto y se formatean según destino:
        // el CSV/Excel necesita punto decimal y sin separador de miles, porque
        // la coma partiría la celda y desordenaría las columnas.
        $decimals = static fn(float $value): string => $isPdf
            ? number_format($value, 2)
            : number_format($value, 2, '.', '');

        $rows = array_map(
            fn($row) => array_map(fn($cell) => is_float($cell) ? $decimals($cell) : $cell, $row),
            $rows
        );
        $totals = array_map(
            fn($value) => is_float($value) ? APP_CURRENCY_SYMBOL . ' ' . $decimals($value) : $value,
            $totals
        );

        if ($isPdf) {
            ReportPdf::generate($titulo, $subtitulo, $headers, $rows, $totals, $filename, $emisor);
            exit();
        }

        $contentType = $format === 'excel'
            ? 'application/vnd.ms-excel'
            : 'text/csv';

        header('Content-Type: ' . $contentType . '; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: max-age=0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_map([$this, 'csvCell'], $row));
        }
        if (!empty($totals)) {
            fputcsv($out, []);
            foreach ($totals as $label => $valor) {
                fputcsv($out, array_map([$this, 'csvCell'], [$label, $valor]));
            }
        }
        fclose($out);
        exit();
    }

    /**
     * Escapa el prefijo de fórmula de celdas que Excel/Sheets interpretaría
     * al abrir el CSV: cualquier celda que empiece por =, +, -, @, tabulación o retorno de carro
     * y no sea un número (un `-1+cmd|...` empieza por dígito tras el signo, pero no es numérico).
     */
    private function csvCell(mixed $value): string
    {
        $text = (string) $value;

        if ($text !== '' && preg_match('/^[=+\-@\t\r]/', $text) === 1 && !is_numeric($text)) {
            return "'" . $text;
        }

        return $text;
    }
}

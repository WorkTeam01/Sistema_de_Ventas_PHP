<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\ReportFilters;
use App\Helpers\ReportPdf;
use App\Models\Category;
use App\Models\Report;

class ReportController extends Controller
{
    public function index(): void
    {
        $session = $this->sessionData();
        $filters = ReportFilters::parseDateRange([]);
        $report  = new Report();

        $userId = Auth::can('view_sales_all')
            ? null
            : (int)Auth::user()['id_usuario'];

        $salesSummary     = $report->salesSummary($filters['fecha_desde'], $filters['fecha_hasta'], $userId);
        $purchaseSummary  = Auth::can('view_purchases_report') ? $report->purchasesTotals($filters['fecha_desde'], $filters['fecha_hasta']) : null;
        $topProductos     = Auth::can('view_top_products_report') ? $report->topProducts($filters['fecha_desde'], $filters['fecha_hasta'], 5) : [];
        $clientesActivos  = Auth::can('view_clients_report') ? count($report->clientsByPeriod($filters['fecha_desde'], $filters['fecha_hasta'])) : 0;

        $this->renderWithLayout(
            'views/reports/index.php',
            array_merge($session, [
                'filters'         => $filters,
                'salesSummary'    => $salesSummary,
                'purchaseSummary' => $purchaseSummary,
                'topProductos'    => $topProductos,
                'clientesActivos' => $clientesActivos,
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
        $userId = Auth::can('view_sales_all')
            ? null
            : (int)Auth::user()['id_usuario'];

        $rows   = $report->salesByPeriod($filters['fecha_desde'], $filters['fecha_hasta'], $userId);
        $totals = $report->salesTotals($filters['fecha_desde'], $filters['fecha_hasta'], $userId);

        $export = trim($_GET['export'] ?? '');
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

        $rows   = $report->purchasesByPeriod($filters['fecha_desde'], $filters['fecha_hasta']);
        $totals = $report->purchasesTotals($filters['fecha_desde'], $filters['fecha_hasta']);

        $export = trim($_GET['export'] ?? '');
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

        $topWhitelist = [5, 10, 20, 50];
        $top       = in_array((int)($_GET['top'] ?? 10), $topWhitelist, true) ? (int)$_GET['top'] : 10;
        $orden     = in_array($_GET['orden'] ?? '', ['cantidad', 'ingresos'], true) ? $_GET['orden'] : 'cantidad';
        $categoria = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int)$_GET['categoria'] : 0;

        $rows       = $report->topProducts($filters['fecha_desde'], $filters['fecha_hasta'], $top, $orden, $categoria);
        $categories = (new Category())->all();

        $export = trim($_GET['export'] ?? '');
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

        $rows = $report->clientsByPeriod($filters['fecha_desde'], $filters['fecha_hasta']);

        $export = trim($_GET['export'] ?? '');
        if ($export !== '') {
            $this->exportClients($export, $filters, $rows);
            return;
        }

        $this->renderWithLayout(
            'views/reports/clients.php',
            array_merge($this->sessionData(), [
                'filters'     => $filters,
                'rows'        => $rows,
                'pageStyles'  => ['/css/modules/reports/reports.css'],
                'pageScripts' => ['/js/modules/reports/reports.js'],
            ]),
            true,
            ['datatable']
        );
    }

    // ── Export helpers ────────────────────────────────────────────────────────

    private function exportSales(string $format, array $filters, array $rows, array $totals): void
    {
        $headers = ['N° Venta', 'Fecha', 'Cliente', 'Total (Bs.)'];
        $data = array_map(fn($r) => [
            $r['nro_venta'],
            date('d/m/Y H:i', strtotime($r['fyh_creacion'])),
            $r['cliente'],
            number_format((float)$r['total_pagado'], 2),
        ], $rows);

        $subtitulo = 'Período: ' . date('d/m/Y', strtotime($filters['desde_display'])) . ' — ' . date('d/m/Y', strtotime($filters['hasta_display']));
        $totalRows = [
            'N° Ventas'     => $totals['num_ventas'],
            'Total Bs.'     => 'Bs. ' . number_format((float)$totals['total_ingresos'], 2),
            'Ticket Prom.'  => 'Bs. ' . number_format((float)$totals['ticket_promedio'], 2),
        ];

        $this->dispatchExport($format, 'Reporte de Ventas', $subtitulo, $headers, $data, $totalRows, 'reporte_ventas');
    }

    private function exportPurchases(string $format, array $filters, array $rows, array $totals): void
    {
        $headers = ['N° Compra', 'Fecha', 'Proveedor', 'Registrado por', 'Total (Bs.)'];
        $data = array_map(fn($r) => [
            $r['nro_compra'],
            date('d/m/Y', strtotime($r['fecha_compra'])),
            $r['proveedor'],
            $r['registrado_por'],
            number_format((float)$r['monto_total'], 2),
        ], $rows);

        $subtitulo = 'Período: ' . date('d/m/Y', strtotime($filters['desde_display'])) . ' — ' . date('d/m/Y', strtotime($filters['hasta_display']));
        $totalRows = [
            'N° Compras'  => $totals['num_compras'],
            'Total Bs.'   => 'Bs. ' . number_format((float)$totals['total_egresos'], 2),
        ];

        $this->dispatchExport($format, 'Reporte de Compras', $subtitulo, $headers, $data, $totalRows, 'reporte_compras');
    }

    private function exportTopProducts(string $format, array $filters, array $rows): void
    {
        $headers = ['Producto', 'Categoría', 'Unidades Vendidas', 'Ingresos (Bs.)'];
        $data = array_map(fn($r) => [
            $r['nombre'],
            $r['categoria'] ?? '—',
            $r['unidades_vendidas'],
            number_format((float)$r['ingresos'], 2),
        ], $rows);

        $subtitulo = 'Período: ' . date('d/m/Y', strtotime($filters['desde_display'])) . ' — ' . date('d/m/Y', strtotime($filters['hasta_display']));

        $this->dispatchExport($format, 'Top Productos más Vendidos', $subtitulo, $headers, $data, [], 'reporte_top_productos');
    }

    private function exportClients(string $format, array $filters, array $rows): void
    {
        $headers = ['Cliente', 'NIT/CI', 'Email', 'N° Compras', 'Monto Acumulado (Bs.)', 'Última Compra'];
        $data = array_map(fn($r) => [
            $r['cliente'],
            $r['nit_ci'],
            $r['email'],
            $r['num_compras'],
            number_format((float)$r['monto_acumulado'], 2),
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

        if ($format === 'pdf') {
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
            fputcsv($out, $row);
        }
        if (!empty($totals)) {
            fputcsv($out, []);
            foreach ($totals as $label => $valor) {
                fputcsv($out, [$label, $valor]);
            }
        }
        fclose($out);
        exit();
    }
}

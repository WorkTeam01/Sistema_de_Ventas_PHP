<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Client;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;

class DashboardController extends Controller
{
    public function index(): void
    {
        $sessionData = $this->sessionData();

        $verVentas   = Auth::can('view_sales');
        $verCompras  = Auth::can('view_purchases');
        $verClientes = Auth::can('view_clients');

        // Sin *_all, cada usuario solo ve sus propios registros de ventas/compras.
        $idUsuario     = (int)(Auth::user()['id_usuario'] ?? 0);
        $ventasUserId  = Auth::can('view_sales_all') ? null : $idUsuario;
        $comprasUserId = Auth::can('view_purchases_all') ? null : $idUsuario;

        $productModel  = new Product();
        $purchaseModel = new Purchase();
        $saleModel     = new Sale();
        $clientModel   = new Client();

        $kpis = [];

        // Stock bajo — todos los roles
        $kpis['low_stock_count'] = $productModel->countLowStock();
        $kpis['stock_critico']   = $kpis['low_stock_count'] > 0;

        // Ventas — cualquier rol con permiso view_sales
        if ($verVentas) {
            $ventasMes          = $saleModel->totalCurrentMonth($ventasUserId);
            $ventasMesAnterior  = $saleModel->totalPreviousMonth($ventasUserId);
            $kpis['ventas_mes'] = $ventasMes;
            $var                = $ventasMesAnterior > 0
                ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1)
                : ($ventasMes > 0 ? 100.0 : 0.0);
            $kpis['ventas_var']     = $var;
            $kpis['ventas_var_cls'] = $var > 0 ? 'text-success' : ($var < 0 ? 'text-danger' : 'text-muted');
            $kpis['ventas_var_ico'] = $var > 0 ? 'fa-arrow-up' : ($var < 0 ? 'fa-arrow-down' : 'fa-minus');
            $kpis['ventas_var_bar'] = min(abs($var), 100);
            $kpis['ventas_hoy']     = $saleModel->todaySummary($ventasUserId);
            $kpis['ultimas_ventas'] = $saleModel->latest(5, $ventasUserId);
            $kpis['ventas_por_mes'] = $saleModel->totalsByMonth(6, $ventasUserId);
            $kpis['top_productos']  = $productModel->getTopSelling(5, $ventasUserId);
        }

        // Compras — cualquier rol con permiso view_purchases
        if ($verCompras) {
            $comprasMes          = $purchaseModel->totalCurrentMonth($comprasUserId);
            $comprasMesAnterior  = $purchaseModel->totalPreviousMonth($comprasUserId);
            $kpis['compras_mes'] = $comprasMes;
            $var                 = $comprasMesAnterior > 0
                ? round((($comprasMes - $comprasMesAnterior) / $comprasMesAnterior) * 100, 1)
                : ($comprasMes > 0 ? 100.0 : 0.0);
            $kpis['compras_var']     = $var;
            $kpis['compras_var_cls'] = $var > 0 ? 'text-danger' : ($var < 0 ? 'text-success' : 'text-muted');
            $kpis['compras_var_ico'] = $var > 0 ? 'fa-arrow-up' : ($var < 0 ? 'fa-arrow-down' : 'fa-minus');
            $kpis['compras_var_bar'] = min(abs($var), 100);
            $kpis['compras_por_mes'] = $purchaseModel->totalsByMonth(6, $comprasUserId);
        }

        // Utilidad bruta — requiere ver ventas y compras a la vez
        if ($verVentas && $verCompras) {
            $kpis['utilidad_bruta']    = ($kpis['ventas_mes'] ?? 0) - ($kpis['compras_mes'] ?? 0);
            $kpis['utilidad_positiva'] = $kpis['utilidad_bruta'] >= 0;
        }

        // Clientes nuevos — requiere permiso view_clients
        if ($verClientes) {
            $kpis['clientes_nuevos'] = $clientModel->countNewThisMonth();
        }

        // Datos del gráfico
        $mesesEs   = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $mesesBase = [];
        for ($i = 5; $i >= 0; $i--) {
            $mesesBase[date('Y-m', strtotime("-$i months"))] = 0;
        }

        $chartLabels   = [];
        $chartDatasets = [];

        foreach (array_keys($mesesBase) as $mesKey) {
            [$anio, $mes] = explode('-', $mesKey);
            $chartLabels[] = $mesesEs[(int)$mes - 1] . ' ' . $anio;
        }

        if (!empty($kpis['ventas_por_mes'])) {
            $ventasPorMes = $mesesBase;
            foreach ($kpis['ventas_por_mes'] as $row) {
                $ventasPorMes[$row['mes']] = (float)$row['total'];
            }
            $chartDatasets[] = [
                'label'           => 'Ventas',
                'data'            => array_values($ventasPorMes),
                'backgroundColor' => 'rgba(40,167,69,0.75)',
                'borderColor'     => 'rgba(40,167,69,1)',
                'borderWidth'     => 1,
            ];
        }

        if (!empty($kpis['compras_por_mes'])) {
            $comprasPorMes = $mesesBase;
            foreach ($kpis['compras_por_mes'] as $row) {
                $comprasPorMes[$row['mes']] = (float)$row['total'];
            }
            $chartDatasets[] = [
                'label'           => 'Compras',
                'data'            => array_values($comprasPorMes),
                'backgroundColor' => 'rgba(220,53,69,0.75)',
                'borderColor'     => 'rgba(220,53,69,1)',
                'borderWidth'     => 1,
            ];
        }

        $chartData = ['labels' => $chartLabels, 'datasets' => $chartDatasets];

        // Dataset para el doughnut de top productos
        $topChart = ['labels' => [], 'quantities' => [], 'revenues' => []];
        if (!empty($kpis['top_productos'])) {
            foreach ($kpis['top_productos'] as $p) {
                $topChart['labels'][]     = $p['nombre'];
                $topChart['quantities'][] = (int)$p['cantidad_vendida'];
                $topChart['revenues'][]   = round((float)$p['ingresos'], 2);
            }
        }

        // Clase de columna Bootstrap según cuántos KPIs ve el rol
        $kpiCount = 1;
        if ($verVentas) {
            $kpiCount += 2;
        }
        if ($verCompras) {
            $kpiCount += 1;
        }
        $kpiCol = $kpiCount >= 4 ? 'col-lg-3 col-md-6' : ($kpiCount === 3 ? 'col-lg-4 col-md-6' : 'col-lg-6 col-md-6');

        $this->renderWithLayout('views/dashboard/index.php', array_merge($sessionData, [
            'kpis'        => $kpis,
            'chartData'   => $chartData,
            'topChart'    => $topChart,
            'kpiCol'      => $kpiCol,
            'pageStyles'  => ['/css/modules/dashboard/dashboard.css'],
            'pageScripts' => ['/js/modules/dashboard/dashboard.js'],
        ]), true, ['chart']);
    }
}

<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;

class DashboardController extends Controller
{
    public function index(): void
    {
        $sessionData = $this->sessionData();
        $rol         = $sessionData['rol_sesion'];

        $productModel  = new Product();
        $purchaseModel = new Purchase();
        $saleModel     = new Sale();

        $kpis = [];

        // Stock bajo — todos los roles
        $kpis['low_stock_count']    = $productModel->countLowStock();
        $kpis['low_stock_products'] = $productModel->lowStockProducts(10);
        $kpis['stock_critico']      = $kpis['low_stock_count'] > 0;

        foreach ($kpis['low_stock_products'] as &$prod) {
            $prod['critico'] = (int)$prod['stock'] === 0;
        }
        unset($prod);

        // Ventas — Admin y Vendedor
        if ($rol === 'Administrador' || $rol === 'Vendedor') {
            $ventasMes          = $saleModel->totalCurrentMonth();
            $ventasMesAnterior  = $saleModel->totalPreviousMonth();
            $kpis['ventas_mes'] = $ventasMes;
            $var                = $ventasMesAnterior > 0
                ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1)
                : ($ventasMes > 0 ? 100.0 : 0.0);
            $kpis['ventas_var']     = $var;
            $kpis['ventas_var_cls'] = $var > 0 ? 'text-success' : ($var < 0 ? 'text-danger' : 'text-muted');
            $kpis['ventas_var_ico'] = $var > 0 ? 'fa-arrow-up' : ($var < 0 ? 'fa-arrow-down' : 'fa-minus');
            $kpis['ventas_var_bar'] = min(abs($var), 100);
            $kpis['ventas_hoy']     = $saleModel->todaySummary();
            $kpis['ultimas_ventas'] = $saleModel->latest(5);
            $kpis['ventas_por_mes'] = $saleModel->totalsByMonth(6);
        }

        // Compras — Admin y Comprador
        if ($rol === 'Administrador' || $rol === 'Comprador') {
            $comprasMes          = $purchaseModel->totalCurrentMonth();
            $comprasMesAnterior  = $purchaseModel->totalPreviousMonth();
            $kpis['compras_mes'] = $comprasMes;
            $var                 = $comprasMesAnterior > 0
                ? round((($comprasMes - $comprasMesAnterior) / $comprasMesAnterior) * 100, 1)
                : ($comprasMes > 0 ? 100.0 : 0.0);
            $kpis['compras_var']     = $var;
            $kpis['compras_var_cls'] = $var > 0 ? 'text-danger' : ($var < 0 ? 'text-success' : 'text-muted');
            $kpis['compras_var_ico'] = $var > 0 ? 'fa-arrow-up' : ($var < 0 ? 'fa-arrow-down' : 'fa-minus');
            $kpis['compras_var_bar'] = min(abs($var), 100);
            $kpis['compras_por_mes'] = $purchaseModel->totalsByMonth(6);
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

        // Clase de columna Bootstrap según cuántos KPIs ve el rol
        $kpiCount = 1;
        if ($rol === 'Administrador' || $rol === 'Vendedor') {
            $kpiCount += 2;
        }
        if ($rol === 'Administrador' || $rol === 'Comprador') {
            $kpiCount += 1;
        }
        $kpiCol = $kpiCount >= 4 ? 'col-lg-3 col-md-6' : ($kpiCount === 3 ? 'col-lg-4 col-md-6' : 'col-lg-6 col-md-6');

        $this->renderWithLayout('views/dashboard/index.php', array_merge($sessionData, [
            'kpis'        => $kpis,
            'chartData'   => $chartData,
            'kpiCol'      => $kpiCol,
            'pageStyles'  => ['/css/modules/dashboard/dashboard.css'],
            'pageScripts' => ['/js/modules/dashboard/dashboard.js'],
        ]), true, ['chart']);
    }
}
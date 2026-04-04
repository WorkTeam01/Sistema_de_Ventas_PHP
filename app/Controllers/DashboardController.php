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

        // Ventas — Admin y Vendedor
        if ($rol === 'Administrador' || $rol === 'Vendedor') {
            $ventasMes          = $saleModel->totalCurrentMonth();
            $ventasMesAnterior  = $saleModel->totalPreviousMonth();
            $kpis['ventas_mes'] = $ventasMes;
            $kpis['ventas_var'] = $ventasMesAnterior > 0
                ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1)
                : ($ventasMes > 0 ? 100.0 : 0.0);
            $kpis['ventas_hoy']     = $saleModel->todaySummary();
            $kpis['ultimas_ventas'] = $saleModel->latest(5);
            $kpis['ventas_por_mes'] = $saleModel->totalsByMonth(6);
        }

        // Compras — Admin y Comprador
        if ($rol === 'Administrador' || $rol === 'Comprador') {
            $comprasMes          = $purchaseModel->totalCurrentMonth();
            $comprasMesAnterior  = $purchaseModel->totalPreviousMonth();
            $kpis['compras_mes'] = $comprasMes;
            $kpis['compras_var'] = $comprasMesAnterior > 0
                ? round((($comprasMes - $comprasMesAnterior) / $comprasMesAnterior) * 100, 1)
                : ($comprasMes > 0 ? 100.0 : 0.0);
            $kpis['compras_por_mes'] = $purchaseModel->totalsByMonth(6);
        }

        $this->renderWithLayout('views/dashboard/index.php', array_merge($sessionData, [
            'kpis'        => $kpis,
            'pageStyles'  => ['/css/modules/dashboard/dashboard.css'],
            'pageScripts' => ['/js/modules/dashboard/dashboard.js'],
        ]), true, ['chart']);
    }
}
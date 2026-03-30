<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Muestra el dashboard principal con totales de cada módulo.
     */
    public function index(): void
    {
        $userModel  = new User();
        $total_user = $userModel->countAll();

        $roleModel   = new Role();
        $total_roles = $roleModel->count();

        $categoryModel    = new Category();
        $total_categorias = $categoryModel->count();

        $sessionData = $this->sessionData();

        $supplierModel    = new Supplier();
        $total_proveedores = $supplierModel->count();

        $clientModel    = new Client();
        $total_clientes = $clientModel->count();

        $productModel              = new Product();
        $total_productos_dashboard = $productModel->count();

        $purchaseModel = new Purchase();
        $total_compras = $purchaseModel->count();

        $saleModel    = new Sale();
        $total_ventas = $saleModel->count();

        $this->renderWithLayout('views/dashboard/index.php', array_merge($sessionData, [
            'total_user'                => $total_user,
            'total_roles'               => $total_roles,
            'total_categorias'          => $total_categorias,
            'total_productos_dashboard' => $total_productos_dashboard,
            'total_proveedores'         => $total_proveedores,
            'total_compras'             => $total_compras,
            'total_ventas'              => $total_ventas,
            'total_clientes'            => $total_clientes,
        ]));
    }
}

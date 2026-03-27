<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Client;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Muestra el dashboard principal con totales de cada módulo.
     * Los módulos aún no migrados a MVC inyectan su total vía require_once legacy.
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
        $pdo = $sessionData['pdo'];

        $supplierModel    = new Supplier();
        $total_proveedores = $supplierModel->count();

        $clientModel    = new Client();
        $total_clientes = $clientModel->count();

        // Los listado files legacy necesitan $pdo en scope local
        require_once __DIR__ . '/../../app/controllers/almacen/listado_de_productos.php';
        require_once __DIR__ . '/../../app/controllers/compras/listado_de_compras.php';
        require_once __DIR__ . '/../../app/controllers/ventas/listado_de_ventas.php';

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

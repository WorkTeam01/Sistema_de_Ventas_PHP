<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Role;
use App\Models\User;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::startSession();

        $usuario           = Auth::user();
        $id_usuario_sesion = $usuario['id_usuario'];
        $nombres_sesion    = $usuario['nombres'];
        $rol_sesion        = $usuario['rol'];

        $userModel  = new User();
        $total_user = $userModel->countAll();

        $roleModel   = new Role();
        $total_roles = $roleModel->count();

        // Los listado files legacy necesitan $pdo en scope local
        $pdo = Database::getInstance()->getConnection();
        require_once __DIR__ . '/../../app/controllers/categorias/listado_de_categorias.php';
        require_once __DIR__ . '/../../app/controllers/almacen/listado_de_productos.php';
        require_once __DIR__ . '/../../app/controllers/proveedores/listado_de_proveedores.php';
        require_once __DIR__ . '/../../app/controllers/compras/listado_de_compras.php';
        require_once __DIR__ . '/../../app/controllers/ventas/listado_de_ventas.php';
        require_once __DIR__ . '/../../app/controllers/clientes/listado_de_clientes.php';

        $this->renderWithLayout('views/dashboard/index.php', [
            'URL'                       => BASE_URL,
            'pdo'                       => $pdo,
            'id_usuario_sesion'         => $id_usuario_sesion,
            'nombres_sesion'            => $nombres_sesion,
            'rol_sesion'                => $rol_sesion,
            'total_user'                => $total_user,
            'total_roles'               => $total_roles,
            'total_categorias'          => $total_categorias,
            'total_productos_dashboard' => $total_productos_dashboard,
            'total_proveedores'         => $total_proveedores,
            'total_compras'             => $total_compras,
            'total_ventas'              => $total_ventas,
            'total_clientes'            => $total_clientes,
        ]);
    }
}

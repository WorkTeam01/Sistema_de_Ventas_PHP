        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-2">
            <!-- Brand Logo -->
            <a href="<?= BASE_URL ?>" class="brand-link">
                <img src="<?= BASE_URL ?>/img/logo_2.png" alt="Logo" loading="eager" class="brand-image img-circle elevation-1" style="opacity: .8">
                <span class="brand-text font-weight-light">Pagina principal</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="<?= BASE_URL ?>/templates/AdminLTE-3.2.0/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block"><?= $nombres_sesion; ?></a>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <!-- Modulo de usuarios -->
                        <?php if ($rol_sesion == 'Administrador'): ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>
                                        Usuarios
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/users" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de usuarios</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/users/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear usuarios</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Modulo de roles-->
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/roles" class="nav-link active">
                                    <i class="nav-icon fas fa-address-card"></i>
                                    <p>Roles</p>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Comprador' || $rol_sesion == 'Vendedor') : ?>
                            <!-- Modulo de categorias-->
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/categories" class="nav-link active">
                                    <i class="nav-icon fas fa-tags"></i>
                                    <p>Categorías</p>
                                </a>
                            </li>
                            <!-- Modulo de almacen (MVC) -->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-boxes"></i>
                                    <p>
                                        Almacén
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/products" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de productos</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/products/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Registrar producto</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Comprador') : ?>
                            <!-- Modulo de proveedores -->
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/suppliers" class="nav-link active">
                                    <i class="nav-icon fas fa-truck"></i>
                                    <p>Proveedores</p>
                                </a>
                            </li>
                            <!-- Modulo de compras -->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-shopping-cart"></i>
                                    <p>Compras <i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/purchases" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de compras</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/purchases/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Registrar compra</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Vendedor') : ?>
                            <!-- Modulo de ventas -->
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    <i class="nav-icon fas fa-shopping-basket"></i>
                                    <p>
                                        Ventas
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/sales" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Lista de ventas</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="<?= BASE_URL ?>/sales/create" class="nav-link">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Crear venta</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>

                            <!-- Modulo de clientes -->
                            <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Vendedor') : ?>
                                <li class="nav-item">
                                    <a href="#" class="nav-link active">
                                        <i class="nav-icon fas fa-user-friends"></i>
                                        <p>
                                            Clientes
                                            <i class="right fas fa-angle-left"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        <li class="nav-item">
                                            <a href="<?= BASE_URL ?>/clients" class="nav-link">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Lista de clientes</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="<?= BASE_URL ?>/clients/create" class="nav-link">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>Crear cliente</p>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>/auth/logout" class="nav-link bg-danger">
                                    <i class="nav-icon fas fa-door-closed"></i>
                                    <p>Cerrar sesión</p>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>
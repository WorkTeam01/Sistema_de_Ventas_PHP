<?php
include_once 'app/config.php';
include_once 'layout/sesion.php';

include_once 'layout/parte1.php';

include_once 'app/controllers/usuarios/listado_de_usuarios.php';
include_once 'app/controllers/roles/listado_de_roles.php';
include_once 'app/controllers/categorias/listado_de_categorias.php';
include_once 'app/controllers/almacen/listado_de_productos.php';
include_once 'app/controllers/proveedores/listado_de_proveedores.php';
include_once 'app/controllers/compras/listado_de_compras.php';
include_once 'app/controllers/ventas/listado_de_ventas.php';
include_once 'app/controllers/clientes/listado_de_clientes.php';

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Bienvenido al Sistema - <?php echo $rol_sesion; ?></h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <?php if ($rol_sesion == 'Administrador') : ?>
                    <div class="col-lg-3 col-6">
                        <!-- Tarjeta de usuarios -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo $total_user; ?></h3>
                                <p>Usuarios registrados</p>
                            </div>
                            <a href="<?php echo $URL; ?>/usuarios/create.php">
                                <div class="icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                            </a>
                            <a href="<?php echo $URL; ?>/usuarios" class="small-box-footer">
                                Mas detalles <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <!-- Tarjeta de roles -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $total_roles; ?></h3>
                                <p>Roles registrados</p>
                            </div>
                            <a href="<?php echo $URL; ?>/roles/create.php">
                                <div class="icon">
                                    <i class="fas fa-id-card-alt"></i>
                                </div>
                            </a>
                            <a href="<?php echo $URL; ?>/roles" class="small-box-footer">
                                Mas detalles <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Comprador' || $rol_sesion == 'Vendedor') : ?>
                    <div class="col-lg-3 col-6">
                        <!-- Tarjeta de categorias -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo $total_categorias; ?></h3>
                                <p>Categorias registradas</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-tag"></i>
                            </div>
                            <a href="<?php echo $URL; ?>/categorias" class="small-box-footer">
                                Mas detalles <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <!-- Tarjeta de productos -->
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3><?php echo $total_productos_dashboard; ?></h3>
                                <p>Productos registrados</p>
                            </div>
                            <a href="<?php echo $URL; ?>/almacen/create.php">
                                <div class="icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </a>
                            <a href="<?php echo $URL; ?>/almacen" class="small-box-footer">
                                Mas detalles <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Comprador') : ?>
                    <div class="col-lg-3 col-6">
                        <!-- Tarjet de proveedores -->
                        <div class="small-box bg-secondary">
                            <div class="inner">
                                <h3><?php echo $total_proveedores; ?></h3>
                                <p>Proveedores registrados</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-truck-moving"></i>
                            </div>
                            <a href="<?php echo $URL; ?>/proveedores" class="small-box-footer">
                                Mas detalles <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-3 col-6">
                        <!-- Tarjet de compras -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?php echo $total_compras; ?></h3>
                                <p>Compras registradas</p>
                            </div>
                            <a href="<?php echo $URL; ?>/compras/create.php">
                                <div class="icon">
                                    <i class="fas fa-cart-arrow-down"></i>
                                </div>
                            </a>
                            <a href="<?php echo $URL; ?>/compras" class="small-box-footer">
                                Mas detalles <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($rol_sesion == 'Administrador' || $rol_sesion == 'Vendedor') : ?>
                    <div class="col-lg-3 col-6">
                        <!-- Tarjet de compras -->
                        <div class="small-box bg-indigo">
                            <div class="inner">
                                <h3><?php echo $total_ventas; ?></h3>
                                <p>Ventas registradas</p>
                            </div>
                            <a href="<?php echo $URL; ?>/ventas/create.php">
                                <div class="icon">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                            </a>
                            <a href="<?php echo $URL; ?>/ventas" class="small-box-footer">
                                Mas detalles <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <!-- Tarjet de clientes -->
                        <div class="small-box bg-light">
                            <div class="inner">
                                <h3><?php echo $total_clientes; ?></h3>
                                <p>Clientes registrados</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-tag"></i>
                            </div>
                            <a href="<?php echo $URL; ?>/clientes" class="small-box-footer">
                                Mas detalles <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php include_once 'layout/parte2.php'; ?>
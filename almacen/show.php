<?php
require_once '../app/config.php';
require_once '../views/layout/sesion.php';
require_once '../app/controllers/middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Comprador', 'Vendedor']);

include_once '../views/layout/parte1.php';

include_once '../app/controllers/almacen/cargar_producto.php';

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Detalles del producto <?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Información del producto</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"> <i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="display: block;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-9">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Código</label>
                                                        <input type="text" value="<?php echo htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Categoría</label>
                                                    <input type="text" value="<?php echo htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" disabled>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Nombre del producto</label>
                                                    <input type="text" value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" disabled>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Usuario</label>
                                                        <input type="text" value="<?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <label>Descripción del producto</label>
                                                    <textarea type="text" class="form-control" disabled><?php echo htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Stock</label>
                                                        <input type="text" value="<?php echo $stock; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Stock mínimo</label>
                                                        <input type="text" value="<?php echo $stock_minimo; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Stock máximo</label>
                                                        <input type="number" value="<?php echo $stock_maximo; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Precio de compra</label>
                                                        <input type="number" value="<?php echo $precio_compra; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Precio de venta</label>
                                                        <input type="number" value="<?php echo $precio_venta; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Fecha de ingreso</label>
                                                        <input type="date" value="<?php echo $fecha_ingreso; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Imagen del producto</label>
                                                <img class="rounded mx-auto d-block" src="<?php echo $URL . "/almacen/img_productos/" . $imagen; ?>" width="100%" alt="">
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="form-group">
                                        <a href="<?php echo $URL; ?>/almacen" class="btn btn-secondary mr-1">Volver</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</div>
<!-- /.content -->
<!-- /.content-wrapper -->

<?php include_once '../views/layout/mensajes.php'; ?>
<?php include_once '../views/layout/parte2.php'; ?>
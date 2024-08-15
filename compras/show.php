<?php
include_once '../app/config.php';
include_once '../layout/sesion.php';

include_once '../layout/parte1.php';

include_once '../app/controllers/compras/cargar_compra.php';

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Detalles de la compra</h1>
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
                            <h3 class="card-title">Información de la compra</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"> <i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="display: block;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Producto</label>
                                                        <input type="text" value="<?php echo $producto; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Número de compra</label>
                                                        <input type="text" value="<?php echo $nro_compra; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Fecha de compra</label>
                                                        <input type="text" value="<?php echo $fecha_compra; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Proveedor</label>
                                                        <input type="text" value="<?php echo $nombre_proveedor; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Comprobante</label>
                                                        <input type="text" class="form-control" value="<?php echo $comprobante; ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Usuario</label>
                                                        <input type="text" value="<?php echo $nombres; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Precio de compra</label>
                                                        <input type="text" value="<?php echo $precio_compra; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Cantidad</label>
                                                        <input type="number" value="<?php echo $cantidad; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">

                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="form-group">
                                        <a href="<?php echo $URL; ?>/compras" class="btn btn-secondary mr-1">Volver</a>
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

<?php include_once '../layout/mensajes.php'; ?>
<?php include_once '../layout/parte2.php'; ?>
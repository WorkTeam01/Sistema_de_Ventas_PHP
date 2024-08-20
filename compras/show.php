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
                <div class="col md-9">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Datos de la compra</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse"> <i class="fas fa-minus"></i></button>
                                    </div>
                                </div>
                                <div class="card-body" style="display: block;">
                                    <h5>Datos del producto</h5>
                                    <hr>
                                    <div class="row" style="font-size: 14px;">
                                        <div class="col-md-9">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <input type="text" id="id_producto" class="form-control" hidden>
                                                        <label>Código</label>
                                                        <input type="text" value="<?php echo $codigo; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Categoría</label>
                                                        <input type="text" value="<?php echo $nombre_categoria; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Nombre del producto</label>
                                                        <input type="text" value="<?php echo $producto; ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Usuario</label>
                                                        <input type="text" value="<?php echo $nombre_usuario ?>" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <div class="form-group">
                                                        <label>Descripción del producto</label>
                                                        <textarea type="text" rows="3" class="form-control" disabled><?php echo $descripcion_producto; ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Stock</label>
                                                        <input type="text" class="form-control bg-warning" value="<?php echo $stock; ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Stock mínimo</label>
                                                        <input type="text" class="form-control" value="<?php echo $stock_minimo; ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Stock máximo</label>
                                                        <input type="text" class="form-control" value="<?php echo $stock_maximo; ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Precio de compra</label>
                                                        <input type="text" class="form-control" value="<?php echo $precio_compra_producto ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Precio de venta</label>
                                                        <input type="text" class="form-control" value="<?php echo $precio_venta; ?>" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Fecha de ingreso</label>
                                                        <input type="text" class="form-control" value="<?php echo $fecha_ingreso; ?>" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Imagen del producto</label>
                                                <img class="rounded mx-auto d-block" src="<?php echo $URL . "/almacen/img_productos/" . $imagen; ?>" width="60%" alt="">
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <h5>Datos del proveedor</h5>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <input type="text" class="form-control" id="id_proveedor" hidden>
                                                <label for="nombre_proveedor">Nombre del proveedor</label>
                                                <input type="text" value="<?php echo $nombre_proveedor_tabla; ?>" class="form-control" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="celular">Celular</label>
                                                <input type="number" class="form-control" value="<?php echo $celular_proveedor; ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="telefono">Telefono</label>
                                                <input type="number" class="form-control" value="<?php echo $telefono_proveedor; ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="empresa">Empresa</label>
                                                <input type="text" class="form-control" value="<?php echo $empresa_proveedor; ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" value="<?php echo $email_proveedor; ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="direccion">Dirección</label>
                                                <textarea id="direccion" rows="3" class="form-control" disabled><?php echo $direccion_proveedor; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Detalle de la compra</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Número de compra</label>
                                                <input type="text" class="form-control" value="<?php echo $nro_compra ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Fecha de la compra</label>
                                                <input type="date" class="form-control" value="<?php echo $fecha_compra ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Comprobante de la compra</label>
                                                <input type="text" class="form-control" value="<?php echo $comprobante; ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Precio de la compra</label>
                                                <input type="text" class="form-control" value="<?php echo $precio_compra; ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Cantidad de la compra</label>
                                                <input type="number" class="form-control text-center" value="<?php echo $cantidad; ?>" disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Usuario</label>
                                                <input type="text" class="form-control" value="<?php echo $email_sesion; ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <a href="<?php echo $URL; ?>/compras" class="btn btn-secondary btn-block">Volver</a>
                                </div>
                            </div>
                        </div>
                        <div id="respuesta_create"></div>
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
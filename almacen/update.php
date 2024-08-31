<?php
require_once '../app/config.php';
require_once '../layout/sesion.php';
require_once '../app/controllers/middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Comprador', 'Vendedor']);

include_once '../layout/parte1.php';

include_once '../app/controllers/categorias/listado_de_categorias.php';
include_once '../app/controllers/almacen/cargar_producto.php';

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Actualizar producto</h1>
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
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Edite los datos del producto</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"> <i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="display: block;">
                            <div class="row">
                                <div class="col-md-12">
                                    <form action="<?php echo $URL; ?>/app/controllers/almacen/update.php" method="post" enctype="multipart/form-data">
                                        <input class="form-control" name="id_producto" value="<?php echo $id_producto_get ?>" hidden>
                                        <div class="row">
                                            <div class="col-md-9">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Código</label>
                                                            <input type="text" value="<?php echo $codigo; ?>" class="form-control" disabled>
                                                            <input type="text" name="codigo" value="<?php echo $codigo; ?>" class="form-control" hidden>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label>Categoría</label>
                                                        <div class="d-flex">
                                                            <select name="id_categoria" class="form-control mr-2" required>
                                                                <?php foreach ($categorias_datos as $categorias_dato) {
                                                                    $nombre_categoria_tabla = $categorias_dato['nombre_categoria'];
                                                                    $id_categoria = $categorias_dato['id_categoria'];
                                                                ?>
                                                                    <option value="<?php echo $id_categoria; ?>" <?php if ($nombre_categoria_tabla == $categoria) { ?> selected="selected" <?php } ?>>
                                                                        <?php echo $nombre_categoria_tabla; ?>
                                                                    </option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label>Nombre del producto</label>
                                                        <input type="text" name="nombre" value="<?php echo $nombre; ?>" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Usuario</label>
                                                            <input type="text" value="<?php echo $usuario; ?>" class="form-control" disabled>
                                                            <input type="text" name="id_usuario" value="<?php echo $id_usuario ?>" class="form-control" hidden>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <label>Descripción del producto</label>
                                                        <textarea type="text" rows="2" name="descripcion" class="form-control"><?php echo $descripcion; ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Stock</label>
                                                            <input type="number" value="<?php echo $stock; ?>" name="stock" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Stock mínimo</label>
                                                            <input type="number" value="<?php echo $stock_minimo; ?>" name="stock_minimo" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Stock máximo</label>
                                                            <input type="number" value="<?php echo $stock_maximo; ?>" name="stock_maximo" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Precio de compra</label>
                                                            <input type="number" value="<?php echo $precio_compra; ?>" name="precio_compra" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Precio de venta</label>
                                                            <input type="number" value="<?php echo $precio_venta; ?>" name="precio_venta" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label>Fecha de ingreso</label>
                                                            <input type="date" value="<?php echo $fecha_ingreso; ?>" name="fecha_ingreso" class="form-control" required>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Imagen del producto</label>
                                                    <input type="file" name="image" class="form-control-file" id="file">
                                                    <input type="text" name="image_text" value="<?php echo $imagen; ?>" class="form-control" hidden>
                                                    <output class="mt-3" id="list">
                                                        <img class="img-thumbnail img-fluid mx-auto d-block" src="<?php echo $URL . "/almacen/img_productos/" . $producto_dato['imagen']; ?>" width="80%" alt="Imagen">
                                                    </output>
                                                    <script>
                                                        function archivo(evt) {
                                                            var files = evt.target.files; // FileList object
                                                            // Obtenemos la imagen del campo "file".
                                                            for (var i = 0, f; f = files[i]; i++) {
                                                                //Solo admitimos imágenes.
                                                                if (!f.type.match('image.*')) {
                                                                    continue;
                                                                }
                                                                var reader = new FileReader();
                                                                reader.onload = (function(theFile) {
                                                                    return function(e) {
                                                                        // Insertamos la imagen
                                                                        document.getElementById("list").innerHTML = ['<img class="img-thumbnail img-fluid mx-auto d-block" width="80%" src="', e.target.result, '" width="60%" height="60%" title="', escape(theFile.name), '"/>'].join('');
                                                                    };
                                                                })(f);
                                                                reader.readAsDataURL(f);
                                                            }
                                                        }
                                                        document.getElementById('file').addEventListener('change', archivo, false);
                                                    </script>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group">
                                            <a href="<?php echo $URL; ?>/almacen" class="btn btn-secondary mr-1">Cancelar</a>
                                            <button type="submit" class="btn btn-success">Actualizar producto</button>
                                        </div>
                                    </form>
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
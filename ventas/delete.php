<?php
$id_venta_get = $_GET['id'];
$nro_venta_get = $_GET['nro_venta'];

require_once '../app/config.php';
require_once '../layout/sesion.php';
require_once '../app/controllers/middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Vendedor']);

include_once '../layout/parte1.php';

include_once '../app/controllers/ventas/cargar_venta.php';
include_once '../app/controllers/clientes/cargar_cliente.php';

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Detalle de la venta nro: <?php echo $nro_venta; ?> ¿Esta seguro de eliminarla?</h1>
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
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-danger">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-shopping-bag"></i> Venta Nro: <?php echo $nro_venta; ?></h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mt-3 table-responsive">
                                        <table class="table table-bordered table-sm table-hover table-striped">
                                            <thead class="bg-secondary">
                                                <tr class="text-center">
                                                    <th>Nro</th>
                                                    <th>Producto</th>
                                                    <th>Descripción</th>
                                                    <th>Cantidad</th>
                                                    <th>Precio unitario</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $contador_de_carritos = 0;
                                                $cantidad_total = 0;
                                                $total_precio_unitario = 0;
                                                $precio_total = 0;
                                                $sql_carrito = "SELECT car.*, al.id_producto, al.nombre, al.descripcion, al.precio_venta, al.stock FROM tb_carrito car
                                                INNER JOIN tb_almacen al on car.id_producto = al.id_producto
                                                WHERE nro_venta = '$nro_venta' ORDER BY id_carrito ASC";
                                                $query_carrito = $pdo->prepare($sql_carrito);
                                                $query_carrito->execute();
                                                $carrito_datos = $query_carrito->fetchAll(PDO::FETCH_ASSOC);

                                                foreach ($carrito_datos as $carrito_dato) {
                                                    $id_carrito = $carrito_dato['id_carrito'];
                                                    $contador_de_carritos += 1;
                                                    $cantidad_total = $cantidad_total + $carrito_dato['cantidad'];
                                                    $total_precio_unitario = $total_precio_unitario + floatval($carrito_dato['precio_venta']);
                                                ?>
                                                    <tr>
                                                        <td class="text-center">
                                                            <?php echo $contador_de_carritos; ?>
                                                            <input type="text" value="<?php echo $carrito_dato['id_producto']; ?>" id="id_producto<?php echo $contador_de_carritos; ?>" hidden>
                                                        </td>
                                                        <td><?php echo $carrito_dato['nombre']; ?></td>
                                                        <td><?php echo $carrito_dato['descripcion']; ?></td>
                                                        <td class="text-center">
                                                            <span id="cantidad_carrito<?php echo $contador_de_carritos; ?>"><?php echo $carrito_dato['cantidad']; ?></span>
                                                            <input type="text" class="form-control" value="<?php echo $carrito_dato['stock']; ?>" id="stock_de_inventario<?php echo $contador_de_carritos; ?>" hidden>
                                                        </td>
                                                        <td class="text-center"><?php echo $carrito_dato['precio_venta']; ?></td>
                                                        <td class="text-center">
                                                            <?php
                                                            $cantidad = floatval($carrito_dato['cantidad']);
                                                            $precio_venta = floatval($carrito_dato['precio_venta']);
                                                            echo $subtotal = $cantidad * $precio_venta;
                                                            $precio_total = $precio_total + $subtotal;
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                                <tr>
                                                    <th class="bg-secondary text-right" colspan="3">Total</th>
                                                    <th class="text-center"><?php echo $cantidad_total; ?></th>
                                                    <th class="text-center"><?php echo $total_precio_unitario; ?></th>
                                                    <th class="text-center bg-warning"><?php echo $precio_total; ?></th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-9">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-check"></i> Datos del cliente</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Nombre del cliente</label>
                                        <input type="text" value="<?php echo $nombre_cliente; ?>" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Nit/CI del cliente</label>
                                        <input type="text" value="<?php echo $nit_ci_cliente; ?>" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Celular del cliente</label>
                                        <input type="text" value="<?php echo $celular_cliente; ?>" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Correo del cliente</label>
                                        <input type="text" value="<?php echo $email_cliente; ?>" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shopping-basket"></i> Datos de la venta</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Monto a cancelar</label>
                                <input type="text" class="form-control text-center bg-warning" value="<?php echo $precio_total; ?>" disabled>
                            </div>
                            <hr>
                            <div class="form-group">
                                <button type="button" id="btn_eliminar_venta" class="btn btn-danger btn-block"><i class="fas fa-trash"></i> Eliminar compra</button>
                                <a href="<?php echo $URL; ?>/ventas" class="btn btn-secondary btn-block">Cancelar</a>
                                <div id="respuesta_borrar_venta"></div>
                            </div>
                            <script>
                                $('#btn_eliminar_venta').click(function() {
                                    var id_venta = '<?php echo $id_venta_get; ?>';
                                    var nro_venta = '<?php echo $nro_venta_get; ?>';

                                    actualizar_stock();
                                    borrar_venta();

                                    function actualizar_stock() {
                                        var i = 1;
                                        var n = '<?php echo $contador_de_carritos; ?>';

                                        for (i = 1; i <= n; i++) {
                                            var stocks = '#stock_de_inventario' + i;
                                            var stock_de_inventario = $(stocks).val();

                                            var cantidades = '#cantidad_carrito' + i;
                                            var cantidad_carrito = $(cantidades).html();

                                            var id_productos = '#id_producto' + i;
                                            var id_producto = $(id_productos).val();

                                            var stock_calculado = parseFloat(parseInt(stock_de_inventario) + parseInt(cantidad_carrito));

                                            //alert(stock_de_inventario + " - " + cantidad_carrito + " - " + stock_calculado + " - " + id_producto);
                                            var url2 = "../app/controllers/ventas/actualizar_stock.php";
                                            $.get(url2, {
                                                id_producto: id_producto,
                                                stock_calculado: stock_calculado
                                            }, function(datos) {});
                                        }
                                    }

                                    function borrar_venta() {
                                        var url = "../app/controllers/ventas/borrar_venta.php";
                                        $.get(url, {
                                            id_venta: id_venta,
                                            nro_venta: nro_venta
                                        }, function(datos) {
                                            $('#respuesta_borrar_venta').html(datos);
                                        });
                                    }
                                });
                            </script>
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
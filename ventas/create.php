<?php
require_once '../app/config.php';
require_once '../layout/sesion.php';
require_once '../app/controllers/middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Vendedor']);

include_once '../layout/parte1.php';

include_once '../app/controllers/almacen/listado_de_productos.php';
include_once '../app/controllers/ventas/listado_de_ventas.php';
include_once '../app/controllers/clientes/listado_de_clientes.php';

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Ventas</h1>
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
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-shopping-bag"></i> Venta Nro: <?php echo $contador_de_ventas; ?></h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex">
                                        <b>Carrito</b>
                                        <button type="button" class="btn btn-primary btn-sm ml-2" data-toggle="modal" data-target="#modal-buscar_producto">
                                            <i class="fas fa-search"></i> Buscar producto
                                        </button>
                                    </div>
                                    <!-- Modal para buscar productos -->
                                    <div class="modal fade" id="modal-buscar_producto">
                                        <div class="modal-dialog modal-xl">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary">
                                                    <h4 class="modal-title">Busqueda del producto</h4>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="table table-responsive">
                                                        <table id="example1" class="table table-bordered table-hover table-striped table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-center">Nro</th>
                                                                    <th class="text-center">Seleccionar</th>
                                                                    <th class="text-center">Código</th>
                                                                    <th class="text-center">Categoría</th>
                                                                    <th class="text-center">Nombre</th>
                                                                    <th class="text-center">Imagen</th>
                                                                    <th class="text-center">Descripción</th>
                                                                    <th class="text-center">Stock</th>
                                                                    <th class="text-center">Precio compra</th>
                                                                    <th class="text-center">Precio venta</th>
                                                                    <th class="text-center">Fecha compra</th>
                                                                    <th class="text-center">Usuario</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                foreach ($productos_datos as $producto_dato) {
                                                                    $id_producto = $producto_dato['id_producto'];
                                                                ?>
                                                                    <tr>
                                                                        <td class="text-center"><?php echo $contador_de_ventas; ?></td>
                                                                        <td>
                                                                            <button type="button" class="btn btn-info" id="btn_seleccionar<?php echo $id_producto; ?>">
                                                                                Seleccionado
                                                                            </button>
                                                                            <script>
                                                                                $('#btn_seleccionar<?php echo $id_producto; ?>').click(function() {

                                                                                    var id_producto = '<?php echo $id_producto; ?>';
                                                                                    $('#id_producto').val(id_producto);

                                                                                    var producto = '<?php echo $producto_dato['nombre']; ?>';
                                                                                    $('#producto').val(producto);

                                                                                    var descripcion = '<?php echo $producto_dato['descripcion']; ?>';
                                                                                    $('#descripcion').val(descripcion);

                                                                                    var precio_unitario = '<?php echo $producto_dato['precio_venta']; ?>';
                                                                                    $('#precio_unitario').val(precio_unitario);

                                                                                    $('#cantidad').focus();

                                                                                    //$('#modal-buscar_producto').modal('toggle');
                                                                                });
                                                                            </script>
                                                                        </td>
                                                                        <td><?php echo $producto_dato['codigo']; ?></td>
                                                                        <td><?php echo $producto_dato['nombre_categoria']; ?></td>
                                                                        <td><?php echo $producto_dato['nombre']; ?></td>
                                                                        <td>
                                                                            <img class="rounded mx-auto d-block" src="<?php echo $URL . "/almacen/img_productos/" . $producto_dato['imagen']; ?>" width="80" alt="">
                                                                        </td>
                                                                        <td><?php echo $producto_dato['descripcion']; ?></td>
                                                                        <td><?php echo $producto_dato['stock']; ?></td>
                                                                        <td><?php echo $producto_dato['precio_compra']; ?></td>
                                                                        <td><?php echo $producto_dato['precio_venta']; ?></td>
                                                                        <td><?php echo $producto_dato['fecha_ingreso']; ?></td>
                                                                        <td><?php echo $producto_dato['email']; ?></td>
                                                                    </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                        </table>
                                                        <div class="row mt-3">
                                                            <div class="col-md-3">
                                                                <div class="form-group">
                                                                    <input type="text" id="id_producto" class="form-control" hidden>
                                                                    <label>Producto</label>
                                                                    <input type="text" id="producto" class="form-control" disabled>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-5">
                                                                <div class="form-group">
                                                                    <label>Descripción</label>
                                                                    <input type="text" id="descripcion" class="form-control" disabled>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label>Cantidad</label>
                                                                    <input type="text" id="cantidad" class="form-control">
                                                                    <small class="text-danger d-none" id="lbl_cantidad">* Rellene la cantidad</small>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group"><label>Precio unitario</label>
                                                                    <input type="text" id="precio_unitario" class="form-control" disabled>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                                    <button type="button" id="btn_registrar_carrito" class="btn btn-primary">Registrar venta</button>
                                                </div>
                                                <div id="respuesta_carrito"></div>
                                                <script>
                                                    $('#btn_registrar_carrito').click(function() {
                                                        var nro_venta = '<?php echo $contador_de_ventas; ?>';
                                                        var id_producto = $('#id_producto').val();
                                                        var cantidad = $('#cantidad').val();

                                                        if (id_producto == "") {
                                                            alert("El producto está vacio");
                                                            return;
                                                        }

                                                        if (cantidad == "") {
                                                            $('#cantidad').focus();
                                                            $('#lbl_cantidad').removeClass('d-none');
                                                        } else {
                                                            $('#lbl_cantidad').addClass('d-none');

                                                            var url = "../app/controllers/ventas/registrar_carrito.php";
                                                            $.get(url, {
                                                                nro_venta: nro_venta,
                                                                id_producto: id_producto,
                                                                cantidad: cantidad
                                                            }, function(datos) {
                                                                $('#respuesta_carrito').html(datos);
                                                            });
                                                        }
                                                    });
                                                </script>
                                            </div>
                                        </div>
                                    </div>
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
                                                    <th>Acción</th>
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
                                                WHERE nro_venta = '$contador_de_ventas' ORDER BY id_carrito ASC";
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
                                                        <td class="text-center">
                                                            <form action="../app/controllers/ventas/borrar_carrito.php" method="post">
                                                                <input type="text" name="id_carrito" value="<?php echo $id_carrito; ?>" hidden>
                                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Eliminar</button>
                                                            </form>
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
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-check"></i> Datos del cliente</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex">
                                <b>Cliente</b>
                                <button type="button" class="btn btn-primary btn-sm ml-2" data-toggle="modal" data-target="#modal-buscar_cliente">
                                    <i class="fas fa-search"></i> Buscar cliente
                                </button>
                            </div>
                            <!-- Modal para buscar cliente -->
                            <div class="modal fade" id="modal-buscar_cliente">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary">
                                            <h4 class="modal-title">Busqueda del cliente</h4>
                                            <button type="button" class="btn btn-default ml-2" data-toggle="modal" data-target="#modal-agregar_cliente">
                                                <i class="fas fa-user-plus"></i> Registrar cliente
                                            </button>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="table table-responsive">
                                                <table id="example2" class="table table-bordered table-hover table-striped table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-center">Nro</th>
                                                            <th class="text-center">Seleccionar</th>
                                                            <th class="text-center">Nombre del cliente</th>
                                                            <th class="text-center">Nit/CI</th>
                                                            <th class="text-center">Celular</th>
                                                            <th class="text-center">Correo</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $contador_de_clientes = 0;
                                                        foreach ($clientes_datos as $clientes_dato) {
                                                            $id_cliente = $clientes_dato['id_cliente'];
                                                            $contador_de_clientes += 1;
                                                        ?>
                                                            <tr>
                                                                <td class="text-center"><?php echo $contador_de_clientes; ?></td>
                                                                <td class="text-center">
                                                                    <button type="button" id="btn_pasar_cliente<?php echo $id_cliente; ?>" class="btn btn-info">Seleccionar</button>
                                                                    <script>
                                                                        $('#btn_pasar_cliente<?php echo $id_cliente; ?>').on('click', function() {
                                                                            var id_cliente = <?php echo $id_cliente; ?>;
                                                                            $('#id_cliente').val(id_cliente);

                                                                            var nombre_cliente = '<?php echo $clientes_dato['nombre_cliente'] ?>';
                                                                            $('#nombre_cliente').val(nombre_cliente);

                                                                            var nit_ci_cliente = '<?php echo $clientes_dato['nit_ci_cliente'] ?>';
                                                                            $('#nit_ci_cliente').val(nit_ci_cliente);

                                                                            var celular_cliente = '<?php echo $clientes_dato['celular_cliente'] ?>';
                                                                            $('#celular_cliente').val(celular_cliente);

                                                                            var email_cliente = '<?php echo $clientes_dato['email_cliente'] ?>';
                                                                            $('#correo_cliente').val(email_cliente);

                                                                            $('#modal-buscar_cliente').modal('toggle');
                                                                        });
                                                                    </script>
                                                                </td>
                                                                <td><?php echo $clientes_dato['nombre_cliente']; ?></td>
                                                                <td class="text-center"><?php echo $clientes_dato['nit_ci_cliente']; ?></td>
                                                                <td class="text-center"><?php echo $clientes_dato['celular_cliente']; ?></td>
                                                                <td><?php echo $clientes_dato['email_cliente']; ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <input type="text" id="id_cliente" class="form-control" hidden>
                                        <label>Nombre del cliente</label>
                                        <input type="text" id="nombre_cliente" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Nit/CI del cliente</label>
                                        <input type="text" id="nit_ci_cliente" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Celular del cliente</label>
                                        <input type="text" id="celular_cliente" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Correo del cliente</label>
                                        <input type="text" id="correo_cliente" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shopping-basket"></i> Registrar venta</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Monto a cancelar</label>
                                <input type="text" id="total_a_cancelar" class="form-control text-center bg-warning" value="<?php echo $precio_total; ?>" disabled>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Total pagado</label>
                                        <input type="text" id="total_pagado" class="form-control text-center" value="">
                                        <script>
                                            $('#total_pagado').keyup(function() {
                                                var total_a_cancelar = $('#total_a_cancelar').val();
                                                var total_pagado = $('#total_pagado').val();
                                                var cambio = parseFloat(total_pagado) - parseFloat(total_a_cancelar);
                                                $('#cambio').val(cambio);
                                            });
                                        </script>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Cambio</label>
                                        <input type="text" id="cambio" class="form-control text-center" value="" disabled>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <a href="<?php echo $URL; ?>/ventas" class="btn btn-secondary btn-block">Cancelar</a>
                                <button type="button" id="btn_guardar_venta" class="btn btn-primary btn-block">Guardar venta</button>
                                <div id="respuesta_registro_venta"></div>
                                <script>
                                    $('#btn_guardar_venta').click(function() {
                                        var nro_venta = '<?php echo $contador_de_ventas; ?>';
                                        var id_cliente = $('#id_cliente').val();
                                        var total_a_cancelar = $('#total_a_cancelar').val();

                                        if (id_cliente == "") {
                                            alert("Debe seleccionar a un cliente.");
                                            return;
                                        }
                                        guardar_venta();
                                        actualizar_stock();

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

                                                var stock_calculado = parseFloat(stock_de_inventario - cantidad_carrito);

                                                //alert(stock_de_inventario + " - " + cantidad_carrito + " - " + stock_calculado + " - " + id_producto);
                                                var url2 = "../app/controllers/ventas/actualizar_stock.php";
                                                $.get(url2, {
                                                    id_producto: id_producto,
                                                    stock_calculado: stock_calculado
                                                }, function(datos) {});
                                            }
                                        }

                                        function guardar_venta() {
                                            var url = "../app/controllers/ventas/registro_de_ventas.php";
                                            $.get(url, {
                                                nro_venta: nro_venta,
                                                id_cliente: id_cliente,
                                                total_a_cancelar: total_a_cancelar
                                            }, function(datos) {
                                                $('#respuesta_registro_venta').html(datos);
                                            });
                                        }
                                    });
                                </script>
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

<script>
    $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
        "pageLength": 5,
        lengthMenu: [
            [3, 5, 10, 25, 50],
            [3, 5, 10, 25, 50]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ productos",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 productos",
            "sInfoFiltered": "(filtrado de un total de _MAX_ productos)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    });

    $("#example2").DataTable({
        "responsive": true,
        "autoWidth": false,
        "pageLength": 5,
        lengthMenu: [
            [3, 5, 10, 25, 50],
            [3, 5, 10, 25, 50]
        ],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ clientes",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 clientes",
            "sInfoFiltered": "(filtrado de un total de _MAX_ clientes)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    });
</script>

<!-- Modal para registrar nuevo cliente -->
<div class="modal fade" id="modal-agregar_cliente">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-secondary">
                <h4 class="modal-title">Registrar nuevo cliente</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="../app/controllers/clientes/guardar_clientes.php" method="post">
                    <div class="form-group">
                        <label>Nombre del cliente</label>
                        <input type="text" class="form-control" name="nombre_cliente">
                    </div>
                    <div class="form-group">
                        <label>Nit/CI del cliente</label>
                        <input type="text" class="form-control" name="nit_ci_cliente">
                    </div>
                    <div class="form-group">
                        <label>Celular del cliente</label>
                        <input type="text" class="form-control" name="celular_cliente">
                    </div>
                    <div class="form-group">
                        <label>Correo del cliente</label>
                        <input type="email" class="form-control" name="email_cliente">
                    </div>
                    <hr>
                    <div class="form-group">
                        <button type="button" class="btn btn-default btn-block" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-secondary btn-block">Guardar cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
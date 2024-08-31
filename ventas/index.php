<?php
require_once '../app/config.php';
require_once '../layout/sesion.php';
require_once '../app/controllers/middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Vendedor']);

include_once '../layout/parte1.php';

include_once '../app/controllers/ventas/listado_de_ventas.php';

?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Lista de ventas</h1>
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
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Ventas registradas</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"> <i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="display: block;">
                            <div class="table table-responsive">
                                <table id="example1" class="table table-bordered table-hover table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Nro</th>
                                            <th class="text-center">Nro de venta</th>
                                            <th class="text-center">Productos</th>
                                            <th class="text-center">Cliente</th>
                                            <th class="text-center">Total pagado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $contador = 0;
                                        foreach ($ventas_datos as $ventas_dato) {
                                            $id_venta = $ventas_dato['id_venta'];
                                            $id_cliente = $ventas_dato['id_cliente'];
                                            $contador += 1;
                                        ?>
                                            <tr>
                                                <td class="text-center"><?php echo $contador; ?></td>
                                                <td class="text-center"><?php echo $ventas_dato['nro_venta']; ?></td>
                                                <td class="text-center">
                                                    <!-- Button trigger modal -->
                                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#Modal-productos<?php echo $id_venta; ?>">
                                                        <i class="fas fa-shopping-bag"></i> Productos
                                                    </button>

                                                    <!-- Modal -->
                                                    <div class="modal fade" id="Modal-productos<?php echo $id_venta; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-primary">
                                                                    <h5 class="modal-title" id="exampleModalLabel">Productos de la venta: <?php echo $ventas_dato['nro_venta']; ?></h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="table-responsive">
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

                                                                                $nro_venta = $ventas_dato['nro_venta'];
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
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <!-- Button trigger modal -->
                                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#Modal-cliente<?php echo $id_venta; ?>">
                                                        <i class="fas fa-user"></i> <?php echo $ventas_dato['nombre_cliente']; ?>
                                                    </button>

                                                    <!-- Modal -->
                                                    <div class="modal fade" id="Modal-cliente<?php echo $id_venta; ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-sm" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-primary">
                                                                    <h5 class="modal-title" id="exampleModalLabel">Datos del cliente</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <?php
                                                                $sql_clientes = "SELECT * FROM tb_clientes WHERE id_cliente = '$id_cliente'";
                                                                $query_clientes = $pdo->query($sql_clientes);
                                                                $query_clientes->execute();
                                                                $total_clientes = $query_clientes->rowCount();
                                                                $clientes_datos = $query_clientes->fetchAll(PDO::FETCH_ASSOC);

                                                                foreach ($clientes_datos as $clientes_dato) {
                                                                    $id_cliente = $clientes_dato['id_cliente'];
                                                                }
                                                                ?>
                                                                <div class="modal-body">
                                                                    <div class="form-group">
                                                                        <label>Nombre del cliente</label>
                                                                        <input type="text" class="form-control" value="<?php echo $clientes_dato['nombre_cliente']; ?>" disabled>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Nit/CI del cliente</label>
                                                                        <input type="text" class="form-control text-center" value="<?php echo $clientes_dato['nit_ci_cliente']; ?>" disabled>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Celular del cliente</label>
                                                                        <input type="text" class="form-control text-center" value="<?php echo $clientes_dato['celular_cliente']; ?>" disabled>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Correo del cliente</label>
                                                                        <input type="email" class="form-control" value="<?php echo $clientes_dato['email_cliente']; ?>" disabled>
                                                                    </div>
                                                                    <hr>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-primary"><?php echo "Bs. " . $ventas_dato['total_pagado']; ?></button>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <a href="show.php?id=<?php echo $id_venta; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Ver</a>
                                                        <a href="delete.php?id=<?php echo $id_venta; ?>&nro_venta=<?php echo $nro_venta; ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Eliminar</a>
                                                        <a href="factura.php?id=<?php echo $id_venta; ?>&nro_venta=<?php echo $nro_venta; ?>" class="btn btn-success btn-sm"><i class="fas fa-print"></i> Imprimir</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php include_once '../layout/mensajes.php'; ?>
<?php include_once '../layout/parte2.php'; ?>

<!-- Page specific script -->
<script>
    $("#example1").DataTable({
        "responsive": true,
        "autoWidth": false,
        buttons: [{
                extend: 'collection',
                text: 'Reportes',
                orientation: 'landscape',
                buttons: [{
                    text: 'Copiar',
                    extend: 'copy'
                }, {
                    extend: 'pdf',
                }, {
                    extend: 'csv',
                }, {
                    extend: 'excel',
                }, {
                    text: 'Imprimir',
                    extend: 'print'
                }]
            },
            {
                extend: 'colvis',
                text: 'Visualización de columnas'
            }
        ],
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Compras",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Compras",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Compras)",
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
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
</script>
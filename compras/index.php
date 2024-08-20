<?php
include_once '../app/config.php';
include_once '../layout/sesion.php';

include_once '../layout/parte1.php';

include_once '../app/controllers/compras/listado_de_compras.php';

?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Lista de compras</h1>
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
                            <h3 class="card-title">Compras registradas</h3>
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
                                            <th class="text-center">Nro de compra</th>
                                            <th class="text-center">Producto</th>
                                            <th class="text-center">Fecha de compra</th>
                                            <th class="text-center">Proveedor</th>
                                            <th class="text-center">Comprobante</th>
                                            <th class="text-center">Usuario</th>
                                            <th class="text-center">Precio compra</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $contador = 0;
                                        foreach ($compras_datos as $compra_dato) {
                                            $id_compra = $compra_dato['id_compra'];
                                        ?>
                                            <tr>
                                                <td class="text-center"><?php echo $contador += 1; ?></td>
                                                <td class="text-center"><?php echo $compra_dato['nro_compra']; ?></td>
                                                <td>
                                                    <div class="text-center">
                                                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modal-producto<?php echo $id_compra; ?>">
                                                            <?php echo $compra_dato['nombre']; ?>
                                                        </button>
                                                    </div>
                                                    <!-- Modal datos de productos -->
                                                    <div class="modal fade" id="modal-producto<?php echo $id_compra; ?>">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning">
                                                                    <h4 class="modal-title">Detalle de producto</h4>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-9">
                                                                            <div class="row">
                                                                                <div class="col-md-2">
                                                                                    <div class="form-group">
                                                                                        <label for="codigo">Código</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['codigo']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-4">
                                                                                    <div class="form-group">
                                                                                        <label for="nombre">Nombre</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['nombre']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="descripcion">Descripción</label>
                                                                                        <textarea rows="3" class="form-control" disabled><?php echo $compra_dato['descripcion']; ?></textarea>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-3">
                                                                                    <div class="form-group">
                                                                                        <label for="stock">Stock</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['stock']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <div class="form-group">
                                                                                        <label for="stock_minimo">Stock mínimo</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['stock_minimo']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <div class="form-group">
                                                                                        <label for="stock_maximo">Stock máximo</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['stock_maximo']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <div class="form-group">
                                                                                        <label for="fecha_ingreso">Fecha ingreso</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['fecha_ingreso']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-3">
                                                                                    <div class="form-group">
                                                                                        <label for="precio_compra">Precio de compra</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['precio_compra']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <div class="form-group">
                                                                                        <label for="precio_venta">Precio de venta</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['precio_venta']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <div class="form-group">
                                                                                        <label for="categoria">Categoría</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['nombre_categoria']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-3">
                                                                                    <div class="form-group">
                                                                                        <label for="fecha_ingreso">Usuario</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['nombres']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="form-group">
                                                                                <label for="imagen">Imagen del producto</label>
                                                                                <img src="<?php echo $URL . "/almacen/img_productos/" . $compra_dato['imagen']; ?>" width="100%">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer justify-content-between">
                                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Volver</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center"><?php echo $compra_dato['fecha_compra']; ?></td>
                                                <td>
                                                    <div class="text-center">
                                                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modal-proveedor<?php echo $id_compra; ?>">
                                                            <?php echo $compra_dato['nombre_proveedor']; ?>
                                                        </button>
                                                    </div>
                                                    <!-- Modal datos de proveedor -->
                                                    <div class="modal fade" id="modal-proveedor<?php echo $id_compra; ?>">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-warning">
                                                                    <h4 class="modal-title">Detalle de proveedor</h4>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="codigo">Nombres del proveedor</label>
                                                                                        <input type="text" value="<?php echo $compra_dato['nombre_proveedor']; ?>" class="form-control" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="nombre">Celular del proveedor</label>
                                                                                        <div class="text-center">
                                                                                            <a href="http://wa.me/591<?php echo $compra_dato['celular']; ?>" target="_blank" class="btn btn-success">
                                                                                                <i class="fas fa-phone-alt"></i>
                                                                                                <?php echo $compra_dato['celular']; ?>
                                                                                            </a>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="descripcion">Empresa del proveedor</label>
                                                                                        <input type="text" class="form-control" value="<?php echo $compra_dato['empresa']; ?>" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="descripcion">Telefono del proveedor</label>
                                                                                        <input type="text" class="form-control" value="<?php echo $compra_dato['telefono']; ?>" disabled>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="descripcion">Email del proveedor</label>
                                                                                        <input type="text" class="form-control" value="<?php echo $compra_dato['email']; ?>" disabled>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <div class="form-group">
                                                                                        <label for="descripcion">Dirección del proveedor</label>
                                                                                        <input type="text" class="form-control" value="<?php echo $compra_dato['direccion']; ?>" disabled>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer justify-content-between">
                                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Volver</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $compra_dato['comprobante']; ?></td>
                                                <td><?php echo $compra_dato['nombres']; ?></td>
                                                <td><?php echo $compra_dato['precio_compra']; ?></td>
                                                <td><?php echo $compra_dato['cantidad']; ?></td>
                                                <td class=" text-center">
                                                    <div class="btn-group">
                                                        <a href="show.php?id=<?php echo $id_compra; ?>" type="button" class="btn btn-info"><i class="fas fa-eye"></i> Ver</a>
                                                        <a href="update.php?id=<?php echo $id_compra; ?>" type="button" class="btn btn-success"><i class="fas fa-pencil-alt"></i> Editar</a>
                                                        <a href="delete.php?id=<?php echo $id_compra; ?>" type="button" class="btn btn-danger"><i class="fas fa-trash"></i> Eliminar</a>
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
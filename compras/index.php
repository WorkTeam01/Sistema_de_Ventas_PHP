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
                                            <th class="text-center">Producto</th>
                                            <th class="text-center">Nro compra</th>
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
                                                <td><?php echo $compra_dato['nombre']; ?></td>
                                                <td><?php echo $compra_dato['nro_compra']; ?></td>
                                                <td><?php echo $compra_dato['comprobante']; ?></td>
                                                <td><?php echo $compra_dato['nombres']; ?></td>
                                                <td><?php echo $compra_dato['precio_compra']; ?></td>
                                                <td><?php echo $compra_dato['cantidad']; ?></td>
                                                <td class="text-center">
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
<?php
require_once '../app/config.php';
require_once '../layout/sesion.php';
require_once '../app/controllers/middleware/AuthMiddleware.php';

$auth = new AuthMiddleware($pdo, $URL);
$usuario = $auth->verificarRoles(['Administrador', 'Comprador']);
include_once '../layout/parte1.php';

include_once '../app/controllers/proveedores/listado_de_proveedores.php';
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Lista de proveedores
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-create">
                            <i class="fas fa-plus"></i> Crear nuevo
                        </button>
                    </h1>
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
                            <h3 class="card-title">Proveedores registrados</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"> <i class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body" style="display: block;">
                            <div class="table-responsive">
                                <!-- La tabla completa se incluye aquí -->
                                <table id="example1" class="table table-bordered table-hover table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Nro</th>
                                            <th class="text-center">Nombre de proveedor</th>
                                            <th class="text-center">Celular</th>
                                            <th class="text-center">Telefono</th>
                                            <th class="text-center">Empresa</th>
                                            <th class="text-center">Email</th>
                                            <th class="text-center">Dirección</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $contador = 0;
                                        foreach ($proveedores_datos as $proveedores_dato) {
                                            $id_proveedor = $proveedores_dato['id_proveedor'];
                                            $nombre_proveedor = $proveedores_dato['nombre_proveedor'];
                                            $celular = $proveedores_dato['celular'];
                                            $telefono = $proveedores_dato['telefono'];
                                            $empresa = $proveedores_dato['empresa'];
                                            $email = $proveedores_dato['email'];
                                            $direccion = $proveedores_dato['direccion'];
                                        ?>
                                            <tr>
                                                <td class="text-center"><?php echo $contador += 1; ?></td>
                                                <td><?php echo $nombre_proveedor; ?></td>
                                                <td class="text-center">
                                                    <a href="http://wa.me/591<?php echo $celular; ?>" target="_blank" class="btn btn-success btn-sm">
                                                        <i class="fas fa-phone-alt"></i>
                                                        <?php echo $celular; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo $telefono; ?></td>
                                                <td><?php echo $empresa; ?></td>
                                                <td><?php echo $email; ?></td>
                                                <td><?php echo $direccion; ?></td>
                                                <td>
                                                    <div class="text-center">
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal-update<?php echo $id_proveedor; ?>">
                                                                <i class="fas fa-pencil-alt"></i> Editar
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#modal-delete<?php echo $id_proveedor ?>">
                                                                <i class="fas fa-trash-alt"></i> Eliminar
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <!-- Modal update proveedores -->
                                                    <div class="modal fade" id="modal-update<?php echo $id_proveedor; ?>">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-success">
                                                                    <h4 class="modal-title">Actualizar proveedor</h4>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input id="id_proveedor" value="<?php echo $id_proveedor; ?>" class="form-control" hidden>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="nombre_proveedor">Nombre del proveedor</label>
                                                                                <input type="text" id="nombre_proveedor<?php echo $id_proveedor; ?>" value="<?php echo $nombre_proveedor; ?>" name="nombre_proveedor" class="form-control">
                                                                                <small class="text-danger d-none" id="lbl_nombre<?php echo $id_proveedor; ?>">* Este campo es requerido</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="celular">Celular</label>
                                                                                <input type="number" id="celular<?php echo $id_proveedor; ?>" value="<?php echo $celular; ?>" name="celular" class="form-control">
                                                                                <small class="text-danger d-none" id="lbl_celular<?php echo $id_proveedor; ?>">* Este campo es requerido</small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="telefono">Telefono</label>
                                                                                <input type="number" id="telefono<?php echo $id_proveedor; ?>" value="<?php echo $telefono; ?>" name="telefono" class="form-control">
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="empresa">Empresa</label>
                                                                                <input type="text" id="empresa<?php echo $id_proveedor; ?>" value="<?php echo $empresa; ?>" name="empresa" class="form-control">
                                                                                <small class="text-danger d-none" id="lbl_empresa<?php echo $id_proveedor; ?>">* Este campo es requerido</small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="email">Email</label>
                                                                                <input type="text" id="email<?php echo $id_proveedor; ?>" value="<?php echo $email; ?>" name="email" class="form-control">
                                                                                <small class="text-danger d-none" id="lbl_email<?php echo $id_proveedor; ?>">* Este campo es requerido</small>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="direccion">Dirección</label>
                                                                                <textarea type="text" rows="3" id="direccion<?php echo $id_proveedor; ?>" name="direccion" class="form-control"><?php echo $direccion; ?></textarea>
                                                                                <small class="text-danger d-none" id="lbl_direccion<?php echo $id_proveedor; ?>">* Este campo es requerido</small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer justify-content-between">
                                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                                                    <button type="button" class="btn btn-success" id="btn_update<?php echo $id_proveedor; ?>">Actualizar categoría</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <script>
                                                        $('#btn_update<?php echo $id_proveedor; ?>').click(function() {
                                                            var nombre_proveedor = $('#nombre_proveedor<?php echo $id_proveedor; ?>').val();
                                                            var celular = $('#celular<?php echo $id_proveedor; ?>').val();
                                                            var telefono = $('#telefono<?php echo $id_proveedor; ?>').val();
                                                            var empresa = $('#empresa<?php echo $id_proveedor; ?>').val();
                                                            var email = $('#email<?php echo $id_proveedor; ?>').val();
                                                            var direccion = $('#direccion<?php echo $id_proveedor; ?>').val();
                                                            var id_proveedor = '<?php echo $id_proveedor; ?>';

                                                            if (nombre_proveedor == "") {
                                                                $('#nombre_proveedor<?php echo $id_proveedor; ?>').focus();
                                                                $('#lbl_nombre<?php echo $id_proveedor; ?>').removeClass('d-none');
                                                                return;
                                                            } else {
                                                                $('#lbl_nombre<?php echo $id_proveedor; ?>').addClass('d-none');
                                                            }
                                                            if (celular == "") {
                                                                $('#celular<?php echo $id_proveedor; ?>').focus();
                                                                $('#lbl_celular<?php echo $id_proveedor; ?>').removeClass('d-none');
                                                                return;
                                                            } else {
                                                                $('#lbl_celular<?php echo $id_proveedor; ?>').addClass('d-none');
                                                            }
                                                            if (empresa == "") {
                                                                $('#empresa<?php echo $id_proveedor; ?>').focus();
                                                                $('#lbl_empresa<?php echo $id_proveedor; ?>').removeClass('d-none');
                                                                return;
                                                            } else {
                                                                $('#lbl_empresa<?php echo $id_proveedor; ?>').addClass('d-none');
                                                            }
                                                            if (direccion == "") {
                                                                $('#direccion<?php echo $id_proveedor; ?>').focus();
                                                                $('#lbl_direccion<?php echo $id_proveedor; ?>').removeClass('d-none');
                                                                return;
                                                            } else {
                                                                $('#lbl_direccion<?php echo $id_proveedor; ?>').addClass('d-none');
                                                            }
                                                            var url = "../app/controllers/proveedores/update.php";
                                                            $.get(url, {
                                                                id_proveedor: id_proveedor,
                                                                nombre_proveedor: nombre_proveedor,
                                                                celular: celular,
                                                                telefono: telefono,
                                                                empresa: empresa,
                                                                email: email,
                                                                direccion: direccion
                                                            }, function(datos) {
                                                                $('#respuesta_update<?php echo $id_proveedor; ?>').html(datos);
                                                            });
                                                        });
                                                    </script>
                                                    <div id="respuesta_update<?php echo $id_proveedor; ?>"></div>

                                                    <!-- Modal delete proveedores -->
                                                    <div class="modal fade" id="modal-delete<?php echo $id_proveedor; ?>">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-danger">
                                                                    <h4 class="modal-title">¿Estas seguro de eliminar este proveedor?</h4>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input id="id_proveedor" value="<?php echo $id_proveedor; ?>" class="form-control" hidden>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="nombre_proveedor">Nombre del proveedor</label>
                                                                                <input type="text" id="nombre_proveedor<?php echo $id_proveedor; ?>" value="<?php echo $nombre_proveedor; ?>" name="nombre_proveedor" class="form-control" disabled>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="celular">Celular</label>
                                                                                <input type="number" id="celular<?php echo $id_proveedor; ?>" value="<?php echo $celular; ?>" name="celular" class="form-control" disabled>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="telefono">Telefono</label>
                                                                                <input type="number" id="telefono<?php echo $id_proveedor; ?>" value="<?php echo $telefono; ?>" name="telefono" class="form-control" disabled>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="empresa">Empresa</label>
                                                                                <input type="text" id="empresa<?php echo $id_proveedor; ?>" value="<?php echo $empresa; ?>" name="empresa" class="form-control" disabled>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="email">Email</label>
                                                                                <input type="text" id="email<?php echo $id_proveedor; ?>" value="<?php echo $email; ?>" name="email" class="form-control" disabled>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="form-group">
                                                                                <label for="direccion">Dirección</label>
                                                                                <textarea type="text" rows="3" id="direccion<?php echo $id_proveedor; ?>" name="direccion" class="form-control" disabled><?php echo $direccion; ?></textarea>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer justify-content-between">
                                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                                                    <button type="button" class="btn btn-danger" id="btn_delete<?php echo $id_proveedor; ?>">Eliminar categoría</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <script>
                                                        $('#btn_delete<?php echo $id_proveedor; ?>').click(function() {
                                                            var id_proveedor = '<?php echo $id_proveedor; ?>';

                                                            var url2 = "../app/controllers/proveedores/delete.php";

                                                            $.get(url2, {
                                                                id_proveedor: id_proveedor,
                                                            }, function(datos) {
                                                                $('#respuesta_delete<?php echo $id_proveedor; ?>').html(datos);
                                                            });
                                                        });
                                                    </script>
                                                    <div id="respuesta_delete<?php echo $id_proveedor; ?>"></div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                                <!-- Fin de load_proveedor -->
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
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Proveedores",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Proveedores",
            "sInfoFiltered": "(filtrado de un total de _MAX_ Proveedores)",
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

<!-- Modal para creación de Proveedores -->
<div class="modal fade" id="modal-create">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title">Crear proveedor</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre_proveedor">Nombre del proveedor</label>
                            <input type="text" id="nombre_proveedor" class="form-control">
                            <small class="text-danger d-none" id="lbl_nombre">* Este campo es necesario rellenar</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="celular">Celular</label>
                            <input type="number" id="celular" class="form-control">
                            <small class="text-danger d-none" id="lbl_celular">* Este campo es necesario rellenar</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="telefono">Telefono</label>
                            <input type="number" id="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="empresa">Empresa</label>
                            <input type="text" id="empresa" class="form-control">
                            <small class="text-danger d-none" id="lbl_empresa">* Este campo es necesario rellenar</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="direccion">Dirección</label>
                            <textarea id="direccion" rows="3" class="form-control"></textarea>
                            <small class="text-danger d-none" id="lbl_direccion">* Este campo es necesario rellenar</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn_create">Crear proveedor</button>
            </div>
        </div>
    </div>
</div>

<script>
    $('#btn_create').click(function() {
        var nombre_proveedor = $('#nombre_proveedor').val();
        var celular = $('#celular').val();
        var telefono = $('#telefono').val();
        var empresa = $('#empresa').val();
        var email = $('#email').val();
        var direccion = $('#direccion').val();
        var url = "../app/controllers/proveedores/create.php";

        // Limpiar mensajes de error previos
        $('.error').addClass('d-none');

        // Validación de campos
        if (nombre_proveedor == "") {
            $('#nombre').focus();
            $('#lbl_nombre').removeClass('d-none');
            return; // Detener la ejecución
        } else {
            $('#lbl_nombre').addClass('d-none');
        }
        if (celular == "") {
            $('#celular').focus();
            $('#lbl_celular').removeClass('d-none');
            return; // Detener la ejecución
        } else {
            $('#lbl_celular').addClass('d-none');
        }
        if (empresa == "") {
            $('#empresa').focus();
            $('#lbl_empresa').removeClass('d-none');
            return; // Detener la ejecución
        } else {
            $('#lbl_empresa').addClass('d-none');
        }
        if (direccion == "") {
            $('#direccion').focus();
            $('#lbl_direccion').removeClass('d-none');
            return; // Detener la ejecución
        } else {
            $('#lbl_direccion').addClass('d-none');
        }

        // Si todos los campos son válidos, enviar los datos
        $.get(url, {
            nombre_proveedor: nombre_proveedor,
            celular: celular,
            telefono: telefono,
            empresa: empresa,
            email: email,
            direccion: direccion
        }, function(datos) {
            $('#respuesta').html(datos);
        });
    });
</script>

<div id="respuesta"></div>
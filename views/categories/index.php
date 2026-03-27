<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de categorías</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item active">Categorías</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title">Categorías registradas</h3>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/categories/create" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Nueva categoría
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="categoryTable" class="table table-bordered table-hover table-striped table-sm" style="visibility: hidden;">
                                <thead>
                                    <tr>
                                        <th class="text-center">Nro</th>
                                        <th class="text-center">Nombre de categoría</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 0;
                                    foreach ($categories_datos as $category) :
                                        $id_categoria = $category['id_categoria']; ?>
                                        <tr>
                                            <td class="text-center"><?= $contador += 1; ?></td>
                                            <td><?= htmlspecialchars($category['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= BASE_URL ?>/categories/edit/<?= $id_categoria ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-pencil-alt"></i> Editar
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Información</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p>Las categorías permiten organizar los productos del inventario en grupos lógicos.</p>
                            <ul class="list-unstyled mb-2">
                                <li class="mb-1"><i class="fas fa-tags text-primary mr-1"></i> Cada producto pertenece a una categoría.</li>
                                <li class="mb-1"><i class="fas fa-search text-info mr-1"></i> Facilitan la búsqueda y filtrado en el almacén.</li>
                                <li class="mb-1"><i class="fas fa-chart-bar text-success mr-1"></i> Útiles para generar reportes por grupo.</li>
                            </ul>
                            <p class="text-muted mb-0"><small>Solo se puede modificar el nombre; no se puede eliminar una categoría con productos asociados.</small></p>
                        </div>
                    </div>
                </div>
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->

<!-- Page specific script -->
<script>
    $(document).ready(function() {
        $("#categoryTable").DataTable({
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
                "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ Categorías",
                "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 Categorías",
                "sInfoFiltered": "(filtrado de un total de _MAX_ Categorías)",
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
            },
            initComplete: function() {
                $(this.api().table().node()).css('visibility', 'visible');
            }
        }).buttons().container().appendTo('#categoryTable_wrapper .col-md-6:eq(0)');
    });
</script>
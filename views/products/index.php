<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de productos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item active">Almacén</li>
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
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title">Productos registrados</h3>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/products/create" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Nuevo producto
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="productTable" class="table table-bordered table-hover table-striped table-sm" style="visibility: hidden;">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Nro</th>
                                            <th class="text-center">Imagen</th>
                                            <th class="text-center">Código</th>
                                            <th class="text-center">Nombre</th>
                                            <th class="text-center">Categoría</th>
                                            <th class="text-center">Stock</th>
                                            <th class="text-center">Precio venta</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $contador = 0;
                                        foreach ($products_datos as $product) :
                                            $id_producto   = $product['id_producto'];
                                            $stock_actual  = (int) $product['stock'];
                                            $stock_minimo  = (int) $product['stock_minimo'];
                                            $stock_maximo  = (int) $product['stock_maximo'];
                                        ?>
                                            <tr>
                                                <td class="text-center"><?= $contador += 1; ?></td>
                                                <td class="text-center">
                                                    <img class="rounded mx-auto d-block"
                                                        src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($product['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        width="30" alt="<?= htmlspecialchars($product['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </td>
                                                <td><?= htmlspecialchars($product['codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars($product['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars($product['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <?php if ($stock_actual < $stock_minimo) : ?>
                                                    <td class="bg-danger text-center"><?= $stock_actual; ?></td>
                                                <?php elseif ($stock_maximo > 0 && $stock_actual > $stock_maximo) : ?>
                                                    <td class="bg-success text-center"><?= $stock_actual; ?></td>
                                                <?php else : ?>
                                                    <td class="text-center"><?= $stock_actual; ?></td>
                                                <?php endif; ?>
                                                <td class="text-right"><?= htmlspecialchars($product['precio_venta'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <a href="<?= BASE_URL ?>/products/show/<?= $id_producto ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </a>
                                                        <a href="<?= BASE_URL ?>/products/edit/<?= $id_producto ?>" class="btn btn-success btn-sm">
                                                            <i class="fas fa-pencil-alt"></i> Editar
                                                        </a>
                                                        <button type="button" class="btn btn-danger btn-sm"
                                                            onclick="confirmarEliminar(<?= $id_producto ?>, '<?= htmlspecialchars($product['nombre'], ENT_QUOTES, 'UTF-8'); ?>')">
                                                            <i class="fas fa-trash"></i> Eliminar
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
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
</section>
<!-- /.content-wrapper -->

<!-- Formulario oculto para eliminar (POST + CSRF) -->
<form id="formEliminar" method="post" action="<?= BASE_URL ?>/products/delete" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="id_producto" id="eliminarId">
</form>

<!-- Page specific script -->
<script>
    $(document).ready(function() {
        $("#productTable").DataTable({
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
            },
            initComplete: function() {
                $(this.api().table().node()).css('visibility', 'visible');
            }
        }).buttons().container().appendTo('#productTable_wrapper .col-md-6:eq(0)');
    });

    function confirmarEliminar(id, nombre) {
        Swal.fire({
            title: '¿Está seguro?',
            text: 'Se eliminará el producto "' + nombre + '". Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('formEliminar').submit();
            }
        });
    }
</script>
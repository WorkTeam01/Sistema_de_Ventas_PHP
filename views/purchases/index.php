<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Compras</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item active">Compras</li>
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
                                <h3 class="card-title">Lista de compras</h3>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/purchases/create" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Nueva compra
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="purchaseTable" class="table table-bordered table-hover table-striped table-sm" style="visibility: hidden;">
                                <thead>
                                    <tr>
                                        <th class="text-center">Nro</th>
                                        <th class="text-center">N° Compra</th>
                                        <th class="text-center">Producto</th>
                                        <th class="text-center">Proveedor</th>
                                        <th class="text-center">Precio</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-center">Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 0;
                                    foreach ($purchases_datos as $purchase) :
                                        $id_compra   = $purchase['id_compra'];
                                        $id_producto = $purchase['id_producto'];
                                        $cantidad    = (int) $purchase['cantidad'];
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador += 1; ?></td>
                                            <td class="text-center"><?= htmlspecialchars($purchase['nro_compra'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <img src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($purchase['imagen'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    width="30" class="rounded mr-1"
                                                    alt="<?= htmlspecialchars($purchase['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars($purchase['codigo'], ENT_QUOTES, 'UTF-8'); ?> —
                                                <?= htmlspecialchars($purchase['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?= htmlspecialchars($purchase['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-right"><?= htmlspecialchars($purchase['precio_compra'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center"><?= $cantidad; ?></td>
                                            <td class="text-center"><?= htmlspecialchars($purchase['fecha_compra'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= BASE_URL ?>/purchases/show/<?= $id_compra ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> Ver
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/purchases/edit/<?= $id_compra ?>" class="btn btn-success btn-sm">
                                                        <i class="fas fa-pencil-alt"></i> Editar
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm"
                                                        onclick="confirmarEliminar(<?= $id_compra ?>, <?= $id_producto ?>, <?= $cantidad ?>, '<?= htmlspecialchars($purchase['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?>')">
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
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->

<!-- Formulario oculto para eliminar -->
<form id="formEliminar" action="<?= BASE_URL ?>/purchases/delete" method="post" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="id_compra" id="eliminarId">
    <input type="hidden" name="id_producto" id="eliminarProductoId">
    <input type="hidden" name="cantidad" id="eliminarCantidad">
</form>

<script>
    $(document).ready(function() {
        $("#purchaseTable").DataTable({
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
            "pageLength": 10,
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sSearch": "Buscar:",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            },
            initComplete: function() {
                $(this.api().table().node()).css('visibility', 'visible');
            }
        }).buttons().container().appendTo('#purchaseTable_wrapper .col-md-6:eq(0)');
    });

    function confirmarEliminar(id, idProducto, cantidad, nombre) {
        Swal.fire({
            title: '¿Está seguro?',
            text: 'Se eliminará la compra del producto "' + nombre + '" y se revertirá el stock. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('eliminarProductoId').value = idProducto;
                document.getElementById('eliminarCantidad').value = cantidad;
                document.getElementById('formEliminar').submit();
            }
        });
    }
</script>
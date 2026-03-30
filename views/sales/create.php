<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Nueva Venta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/sales"><i class="fas fa-shopping-cart"></i> Ventas</a></li>
                        <li class="breadcrumb-item active">Nueva venta</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">

            <!-- Sección 1: Carrito -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shopping-bag"></i> Venta Nro: <?= (int) $nro_venta ?></h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <b>Carrito</b>
                                <button type="button" class="btn btn-primary btn-sm ml-2"
                                    data-toggle="modal" data-target="#modal-buscar_producto">
                                    <i class="fas fa-search"></i> Buscar producto
                                </button>
                            </div>

                            <!-- Modal de búsqueda de producto -->
                            <div class="modal fade" id="modal-buscar_producto">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary">
                                            <h4 class="modal-title">Búsqueda del producto</h4>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="table-responsive">
                                                <table id="productTable" class="table table-bordered table-hover table-striped table-sm">
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
                                                            <th class="text-center">Precio venta</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php $nro_prod = 0;
                                                        foreach ($products as $product) : ?>
                                                            <tr>
                                                                <td class="text-center"><?= $nro_prod += 1; ?></td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-info btn-sm btn-seleccionar"
                                                                        data-id="<?= (int) $product['id_producto'] ?>"
                                                                        data-nombre="<?= htmlspecialchars($product['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-descripcion="<?= htmlspecialchars($product['descripcion'], ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-precio="<?= htmlspecialchars($product['precio_venta'], ENT_QUOTES, 'UTF-8') ?>">
                                                                        Seleccionar
                                                                    </button>
                                                                </td>
                                                                <td><?= htmlspecialchars($product['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($product['nombre_categoria'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($product['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center">
                                                                    <img src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($product['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                                                        width="50" class="rounded" alt="">
                                                                </td>
                                                                <td><?= htmlspecialchars($product['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><?= (int) $product['stock'] ?></td>
                                                                <td class="text-center">Bs. <?= htmlspecialchars($product['precio_venta'], ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Producto seleccionado</label>
                                                        <input type="text" id="prod_nombre" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Descripción</label>
                                                        <input type="text" id="prod_descripcion" class="form-control" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Cantidad</label>
                                                        <input type="number" id="prod_cantidad" class="form-control" min="1" value="1">
                                                        <small class="text-danger d-none" id="lbl_cantidad">* Ingrese la cantidad</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Precio unitario</label>
                                                        <input type="text" id="prod_precio" class="form-control" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                                            <button type="button" id="btn_agregar_carrito" class="btn btn-primary">
                                                <i class="fas fa-cart-plus"></i> Agregar al carrito
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /Modal producto -->

                            <!-- Tabla del carrito actual -->
                            <div class="table-responsive mt-3">
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
                                        $nro_item       = 0;
                                        $cantidad_total = 0;
                                        $precio_total   = 0.0;
                                        foreach ($cart_items as $item) :
                                            $nro_item++;
                                            $subtotal = (float) $item['cantidad'] * (float) $item['precio_venta'];
                                            $cantidad_total += (int) $item['cantidad'];
                                            $precio_total   += $subtotal;
                                        ?>
                                            <tr>
                                                <td class="text-center"><?= $nro_item ?></td>
                                                <td><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center"><?= (int) $item['cantidad'] ?></td>
                                                <td class="text-center">Bs. <?= htmlspecialchars(number_format((float) $item['precio_venta'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center">Bs. <?= number_format($subtotal, 2) ?></td>
                                                <td class="text-center">
                                                    <form action="<?= BASE_URL ?>/sales/cart/remove" method="post" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                                        <input type="hidden" name="id_carrito" value="<?= (int) $item['id_carrito'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i> Eliminar
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr>
                                            <th class="bg-secondary text-right" colspan="3">Total</th>
                                            <th class="text-center"><?= $cantidad_total ?></th>
                                            <th></th>
                                            <th class="text-center bg-warning">Bs. <?= number_format($precio_total, 2) ?></th>
                                            <th></th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 2 y 3: Cliente + Pago -->
            <div class="row">
                <!-- Sección 2: Cliente -->
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
                            <div class="d-flex align-items-center mb-3">
                                <b>Cliente</b>
                                <button type="button" class="btn btn-primary btn-sm ml-2"
                                    data-toggle="modal" data-target="#modal-buscar_cliente">
                                    <i class="fas fa-search"></i> Buscar cliente
                                </button>
                            </div>

                            <!-- Modal de búsqueda de cliente -->
                            <div class="modal fade" id="modal-buscar_cliente">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary">
                                            <h4 class="modal-title">Búsqueda del cliente</h4>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="table-responsive">
                                                <table id="clientTable" class="table table-bordered table-hover table-striped table-sm">
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
                                                        <?php $nro_cli = 0;
                                                        foreach ($clients as $client) : ?>
                                                            <tr>
                                                                <td class="text-center"><?= $nro_cli += 1; ?></td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-info btn-sm btn-seleccionar-cliente"
                                                                        data-id="<?= (int) $client['id_cliente'] ?>"
                                                                        data-nombre="<?= htmlspecialchars($client['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-nit="<?= htmlspecialchars($client['nit_ci_cliente'], ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-celular="<?= htmlspecialchars($client['celular_cliente'], ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-email="<?= htmlspecialchars($client['email_cliente'], ENT_QUOTES, 'UTF-8') ?>">
                                                                        Seleccionar
                                                                    </button>
                                                                </td>
                                                                <td><?= htmlspecialchars($client['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><?= htmlspecialchars($client['nit_ci_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td class="text-center"><?= htmlspecialchars($client['celular_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                                                <td><?= htmlspecialchars($client['email_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /Modal cliente -->

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Nombre del cliente</label>
                                        <input type="text" id="cliente_nombre" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Nit/CI del cliente</label>
                                        <input type="text" id="cliente_nit" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Celular del cliente</label>
                                        <input type="text" id="cliente_celular" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Correo del cliente</label>
                                        <input type="text" id="cliente_email" class="form-control" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección 3: Pago -->
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
                        <form id="formVenta" action="<?= BASE_URL ?>/sales" method="post">
                            <div class="card-body">

                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="nro_venta" value="<?= (int) $nro_venta ?>">
                                <input type="hidden" name="id_cliente" id="id_cliente_hidden">
                                <input type="hidden" name="total_a_cancelar" id="total_a_cancelar_hidden" value="<?= number_format($precio_total, 2, '.', '') ?>">

                                <div class="form-group">
                                    <label>Monto a cancelar</label>
                                    <input type="text" id="total_a_cancelar_display" class="form-control text-center bg-warning"
                                        value="<?= number_format($precio_total, 2) ?>" disabled>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Total pagado</label>
                                            <input type="text" id="total_pagado" class="form-control text-center">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Cambio</label>
                                            <input type="text" id="cambio" class="form-control text-center" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="<?= BASE_URL ?>/sales" class="btn btn-default w-100 mb-2">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                <button type="submit" id="btn_guardar_venta" class="btn btn-primary w-100">
                                    <i class="fas fa-check"></i> Guardar venta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
</section>
<!-- /.content-wrapper -->

<!-- Form oculto para agregar al carrito -->
<form id="formCarrito" action="<?= BASE_URL ?>/sales/cart/add" method="post" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="nro_venta" value="<?= (int) $nro_venta ?>">
    <input type="hidden" name="id_producto" id="cart_id_producto">
    <input type="hidden" name="cantidad" id="cart_cantidad">
</form>

<script>
    // DataTable para productos en el modal
    $(document).ready(function() {
        $('#productTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 5,
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible",
                "sInfo": "Mostrando _START_ al _END_ de _TOTAL_ productos",
                "sInfoEmpty": "Mostrando 0 de 0 productos",
                "sInfoFiltered": "(filtrado de _MAX_ total)",
                "sSearch": "Buscar:",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            }
        });

        $('#clientTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 5,
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible",
                "sInfo": "Mostrando _START_ al _END_ de _TOTAL_ clientes",
                "sInfoEmpty": "Mostrando 0 de 0 clientes",
                "sInfoFiltered": "(filtrado de _MAX_ total)",
                "sSearch": "Buscar:",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                }
            }
        });
    });

    // Seleccionar producto del modal
    var selectedProductId = '';
    $(document).on('click', '.btn-seleccionar', function() {
        selectedProductId = $(this).data('id');
        $('#prod_nombre').val($(this).data('nombre'));
        $('#prod_descripcion').val($(this).data('descripcion'));
        $('#prod_precio').val($(this).data('precio'));
        $('#prod_cantidad').val(1).focus();
    });

    // Agregar al carrito
    $('#btn_agregar_carrito').click(function() {
        if (!selectedProductId) {
            Swal.fire('Atención', 'Seleccione un producto primero.', 'warning');
            return;
        }
        var cantidad = parseInt($('#prod_cantidad').val());
        if (!cantidad || cantidad < 1) {
            $('#lbl_cantidad').removeClass('d-none');
            $('#prod_cantidad').focus();
            return;
        }
        $('#lbl_cantidad').addClass('d-none');
        $('#cart_id_producto').val(selectedProductId);
        $('#cart_cantidad').val(cantidad);
        $('#formCarrito').submit();
    });

    // Seleccionar cliente del modal
    $(document).on('click', '.btn-seleccionar-cliente', function() {
        $('#id_cliente_hidden').val($(this).data('id'));
        $('#cliente_nombre').val($(this).data('nombre'));
        $('#cliente_nit').val($(this).data('nit'));
        $('#cliente_celular').val($(this).data('celular'));
        $('#cliente_email').val($(this).data('email'));
        $('#modal-buscar_cliente').modal('hide');
    });

    // Calcular cambio
    $('#total_pagado').keyup(function() {
        var cancelar = parseFloat($('#total_a_cancelar_hidden').val()) || 0;
        var pagado = parseFloat($(this).val()) || 0;
        var cambio = pagado - cancelar;
        $('#cambio').val(isNaN(cambio) ? '' : cambio.toFixed(2));
    });

    // Validar antes de enviar la venta
    $('#formVenta').on('submit', function(e) {
        var id_cliente = $('#id_cliente_hidden').val();
        if (!id_cliente) {
            e.preventDefault();
            Swal.fire('Atención', 'Debe seleccionar un cliente antes de guardar la venta.', 'warning');
        }
    });
</script>
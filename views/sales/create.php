<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Nueva Venta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_URL ?>/sales"><i class="fas fa-shopping-cart"></i> Ventas</a>
                        </li>
                        <li class="breadcrumb-item active">Nueva venta</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">

            <?php
            /* Totales para la vista */
            $nro_item = 0;
            $cantidad_total = 0;
            $precio_total = 0.0;
            foreach ($cart_items as $item) {
                $nro_item++;
                $subtotal = (float)$item['cantidad'] * (float)$item['precio_venta'];
                $cantidad_total += (int)$item['cantidad'];
                $precio_total += $subtotal;
            }
            ?>

            <form id="formVenta" action="<?= BASE_URL ?>/sales" method="post" autocomplete="off">

                <!-- Campos ocultos del formulario principal -->
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="nro_venta" value="<?= (int)$nro_venta ?>">
                <input type="hidden" name="id_cliente" id="id_cliente_hidden">
                <input type="hidden" name="total_a_cancelar" id="total_a_cancelar_hidden"
                       value="<?= number_format($precio_total, 2, '.', '') ?>">

                <div class="row">

                    <!-- ===== Columna principal: Wizard de 3 pasos ===== -->
                    <div class="col-md-9 mb-3">
                        <div class="card card-primary card-outline card-outline-tabs">

                            <!-- Tabs numerados -->
                            <div class="card-header p-0 border-bottom-0">
                                <ul class="nav nav-tabs" id="saleTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="tab-cliente-link"
                                           data-toggle="tab" href="#pane-cliente" role="tab">
                                            <i class="fas fa-user mr-1"></i> 1. Cliente
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="tab-carrito-link"
                                           data-toggle="tab" href="#pane-carrito" role="tab">
                                            <i class="fas fa-shopping-cart mr-1"></i> 2. Carrito
                                            <span class="badge ml-1 <?= $nro_item > 0 ? 'badge-primary' : 'badge-secondary' ?>"
                                                  id="badge-cart-count"><?= $nro_item ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="tab-pago-link"
                                           data-toggle="tab" href="#pane-pago" role="tab">
                                            <i class="fas fa-cash-register mr-1"></i> 3. Pago
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Barra de progreso -->
                            <div class="card-header bg-light pt-2 pb-2 mb-0 border-top-0">
                                <div class="progress pos-progress">
                                    <div id="tab-progress"
                                         class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                         role="progressbar"
                                         style="width: 33%"
                                         aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">
                                        Paso 1 de 3
                                    </div>
                                </div>
                            </div>

                            <!-- Contenido de los tabs -->
                            <div class="card-body">
                                <div class="tab-content" id="saleTabsContent">

                                    <!-- ===== Tab 1: Cliente ===== -->
                                    <div class="tab-pane fade show active" id="pane-cliente" role="tabpanel">

                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h5 class="mb-0">Selección de cliente</h5>
                                            <div>
                                                <button type="button" class="btn btn-success btn-sm mr-1"
                                                        data-toggle="modal" data-target="#modal-nuevo_cliente">
                                                    <i class="fas fa-user-plus mr-1"></i> Nuevo cliente
                                                </button>
                                                <button type="button" class="btn btn-primary btn-sm"
                                                        data-toggle="modal" data-target="#modal-buscar_cliente">
                                                    <i class="fas fa-search mr-1"></i> Buscar cliente
                                                </button>
                                            </div>
                                        </div>

                                        <div class="card card-outline card-primary mb-0">
                                            <div class="card-header">
                                                <h3 class="card-title font-weight-bold">Datos del cliente</h3>
                                                <div class="card-tools m-0">
                                                    <button type="button" class="btn btn-tool"
                                                            data-card-widget="collapse">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="alert-sin-cliente" class="alert alert-warning py-2 mb-0">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    No se ha seleccionado ningún cliente. Por favor, busque y seleccione
                                                    un cliente para continuar.
                                                </div>
                                                <div id="cliente-fields" class="d-none">
                                                    <div id="alert-cliente-ok"
                                                         class="alert alert-success alert-dismissible fade show"
                                                         role="alert">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        Cliente seleccionado correctamente.
                                                        <button type="button" class="close" data-dismiss="alert"
                                                                aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group mb-2">
                                                                <label class="small text-muted mb-1">Nombre</label>
                                                                <input type="text" id="cliente_nombre"
                                                                       class="form-control form-control-sm"
                                                                       autocomplete="off" disabled>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group mb-2">
                                                                <label class="small text-muted mb-1">Nit / CI</label>
                                                                <input type="text" id="cliente_nit"
                                                                       class="form-control form-control-sm"
                                                                       autocomplete="off" disabled>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group mb-2">
                                                                <label class="small text-muted mb-1">Celular</label>
                                                                <input type="text" id="cliente_celular"
                                                                       class="form-control form-control-sm"
                                                                       autocomplete="off" disabled>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group mb-0">
                                                                <label class="small text-muted mb-1">Correo</label>
                                                                <input type="text" id="cliente_email"
                                                                       class="form-control form-control-sm"
                                                                       autocomplete="off" disabled>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-right mt-3">
                                            <button type="button" class="btn btn-primary" id="btn-sig-cliente">
                                                Siguiente <i class="fas fa-arrow-right ml-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- /Tab Cliente -->

                                    <!-- ===== Tab 2: Carrito ===== -->
                                    <div class="tab-pane fade" id="pane-carrito" role="tabpanel">

                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h5 class="mb-0">Productos en el carrito</h5>
                                            <button type="button" class="btn btn-primary btn-sm"
                                                    data-toggle="modal" data-target="#modal-buscar_producto">
                                                <i class="fas fa-search mr-1"></i> Buscar producto
                                            </button>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm table-hover table-striped">
                                                <thead class="bg-secondary text-white">
                                                <tr class="text-center">
                                                    <th class="d-none d-sm-table-cell">#</th>
                                                    <th>Producto</th>
                                                    <th class="d-none d-md-table-cell">Descripción</th>
                                                    <th>Cant.</th>
                                                    <th>P. Unit.</th>
                                                    <th>Subtotal</th>
                                                    <th>Acción</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php $i = 0;
                                                foreach ($cart_items as $item) :
                                                    $i++;
                                                    $sub = (float)$item['cantidad'] * (float)$item['precio_venta'];
                                                    ?>
                                                    <tr>
                                                        <td class="text-center d-none d-sm-table-cell"><?= $i ?></td>
                                                        <td><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td class="d-none d-md-table-cell">
                                                            <?= htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                                                        </td>
                                                        <td class="text-center"><?= (int)$item['cantidad'] ?></td>
                                                        <td class="text-center">
                                                            Bs. <?= number_format((float)$item['precio_venta'], 2) ?>
                                                        </td>
                                                        <td class="text-center">Bs. <?= number_format($sub, 2) ?></td>
                                                        <td class="text-center">
                                                            <form action="<?= BASE_URL ?>/sales/cart/remove"
                                                                  method="post" style="display:inline;"
                                                                  class="form-cart-remove">
                                                                <input type="hidden" name="csrf_token"
                                                                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                                                <input type="hidden" name="id_carrito"
                                                                       value="<?= (int)$item['id_carrito'] ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm">
                                                                    <i class="fas fa-trash"></i>
                                                                    <span class="d-none d-md-inline ml-1">Eliminar</span>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($cart_items)) : ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted py-4">
                                                            <i class="fas fa-cart-arrow-down fa-2x mb-2 d-block"></i>
                                                            Sin productos. Usa "Buscar producto" para agregar ítems.
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                                </tbody>
                                                <?php if (!empty($cart_items)) : ?>
                                                    <tfoot>
                                                    <tr class="font-weight-bold bg-light">
                                                        <td class="d-none d-sm-table-cell"></td>
                                                        <td class="text-right">Total</td>
                                                        <td class="d-none d-md-table-cell"></td>
                                                        <td class="text-center"><?= $cantidad_total ?></td>
                                                        <td></td>
                                                        <td class="text-center bg-warning">
                                                            Bs. <?= number_format($precio_total, 2) ?>
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                    </tfoot>
                                                <?php endif; ?>
                                            </table>
                                        </div>

                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="button" class="btn btn-secondary" id="btn-ant-carrito">
                                                <i class="fas fa-arrow-left mr-1"></i> Anterior
                                            </button>
                                            <button type="button" class="btn btn-primary" id="btn-sig-carrito">
                                                Siguiente <i class="fas fa-arrow-right ml-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- /Tab Carrito -->

                                    <!-- ===== Tab 3: Pago ===== -->
                                    <div class="tab-pane fade" id="pane-pago" role="tabpanel">

                                        <h5 class="mb-3">Detalles del pago</h5>

                                        <div class="row justify-content-center">
                                            <div class="col-md-8">

                                                <div class="form-group">
                                                    <label class="text-muted small mb-1">Monto a cancelar</label>
                                                    <input type="text" id="total_a_cancelar_display"
                                                           class="form-control text-center bg-warning font-weight-bold"
                                                           style="font-size: 1.3rem;"
                                                           value="Bs. <?= number_format($precio_total, 2) ?>" disabled>
                                                </div>

                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <div class="form-group">
                                                            <label>Total pagado</label>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Bs.</span>
                                                                </div>
                                                                <input type="text" name="total_pagado"
                                                                       id="total_pagado"
                                                                       class="form-control text-center"
                                                                       placeholder="0.00" autocomplete="off">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div class="form-group">
                                                            <label>Cambio</label>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text">Bs.</span>
                                                                </div>
                                                                <input type="text" id="cambio"
                                                                       class="form-control text-center"
                                                                       placeholder="0.00" disabled>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="button" class="btn btn-secondary" id="btn-ant-pago">
                                                <i class="fas fa-arrow-left mr-1"></i> Anterior
                                            </button>
                                            <button type="submit" class="btn btn-primary" id="btn_guardar_venta">
                                                <i class="fas fa-check mr-1"></i> Guardar venta
                                            </button>
                                        </div>
                                    </div>
                                    <!-- /Tab Pago -->

                                </div>
                                <!-- /tab-content -->
                            </div>
                            <!-- /card-body -->

                        </div>
                    </div>
                    <!-- /Columna principal -->

                    <!-- ===== Columna derecha: Resumen de venta ===== -->
                    <div class="col-md-3 mb-3">
                        <div class="card card-outline card-primary sticky-top" style="top: 10px;">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-receipt mr-1"></i> Resumen de venta
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <small class="text-muted d-block">Nro. Venta</small>
                                        <strong class="text-primary"><?= (int)$nro_venta ?></strong>
                                    </li>
                                    <li class="list-group-item">
                                        <small class="text-muted d-block">Cliente</small>
                                        <span id="resumen-cliente" class="text-muted font-italic">
                                            No seleccionado
                                        </span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <small class="text-muted d-block">Productos</small>
                                            <span id="resumen-cantidad"><?= $nro_item ?></span>
                                        </div>
                                        <span class="badge badge-warning badge-pill px-2 py-1"
                                              id="resumen-total" style="font-size: 0.85rem;">
                                            Bs. <?= number_format($precio_total, 2) ?>
                                        </span>
                                    </li>
                                    <li class="list-group-item">
                                        <small class="text-muted d-block">Estado</small>
                                        <span class="badge badge-warning">Pendiente</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <button type="button" id="btn-cancelar-venta" class="btn btn-default btn-block"
                                        data-nro-venta="<?= (int)$nro_venta ?>"
                                        data-csrf="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- /Columna derecha -->

                </div>
                <!-- /.row -->

            </form>
            <!-- /formVenta -->


        </div><!-- /.container-fluid -->
    </div>
</section>
<!-- /.content-wrapper -->

<!-- ===== Modal: Búsqueda de cliente ===== -->
<div class="modal fade" id="modal-buscar_cliente">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title"><i class="fas fa-users mr-1"></i> Búsqueda del cliente</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="clientTable"
                           class="table table-bordered table-hover table-striped table-sm">
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Seleccionar</th>
                            <th class="text-center">Nombre del cliente</th>
                            <th class="text-center">Nit / CI</th>
                            <th class="text-center">Celular</th>
                            <th class="text-center">Correo</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $nro_cli = 0;
                        foreach ($clients as $client) : ?>
                            <tr>
                                <td class="text-center"><?= ++$nro_cli ?></td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-info btn-sm btn-seleccionar-cliente"
                                            data-id="<?= (int)$client['id_cliente'] ?>"
                                            data-nombre="<?= htmlspecialchars($client['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-nit="<?= htmlspecialchars($client['nit_ci_cliente'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-celular="<?= htmlspecialchars($client['celular_cliente'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars($client['email_cliente'], ENT_QUOTES, 'UTF-8') ?>">
                                        Seleccionar
                                    </button>
                                </td>
                                <td><?= htmlspecialchars($client['nombre_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-center">
                                    <?= htmlspecialchars($client['nit_ci_cliente'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="text-center">
                                    <?= htmlspecialchars($client['celular_cliente'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td><?= htmlspecialchars($client['email_cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Modal cliente -->

<!-- ===== Modal: Búsqueda de producto ===== -->
<div class="modal fade" id="modal-buscar_producto">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title"><i class="fas fa-box mr-1"></i> Búsqueda del producto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="productTable"
                           class="table table-bordered table-hover table-striped table-sm">
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
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
                                <td class="text-center"><?= ++$nro_prod ?></td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-info btn-sm btn-seleccionar"
                                            data-id="<?= (int)$product['id_producto'] ?>"
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
                                <td class="text-center"><?= (int)$product['stock'] ?></td>
                                <td class="text-center">
                                    Bs. <?= htmlspecialchars($product['precio_venta'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Detalle del producto seleccionado -->
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Producto seleccionado</label>
                            <input type="text" id="prod_nombre" class="form-control" autocomplete="off" disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" id="prod_descripcion" rows="2" disabled></textarea>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Cantidad</label>
                            <input type="number" id="prod_cantidad" class="form-control"
                                   autocomplete="off" min="1" value="1">
                            <small class="text-danger d-none" id="lbl_cantidad">* Ingrese la cantidad</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Precio unitario</label>
                            <input type="text" id="prod_precio" class="form-control" autocomplete="off" disabled>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btn_agregar_carrito" class="btn btn-primary">
                    <i class="fas fa-cart-plus mr-1"></i> Agregar al carrito
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Modal producto -->

<!-- ===== Modal: Nuevo cliente ===== -->
<div class="modal fade" id="modal-nuevo_cliente" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-user-plus mr-1"></i> Registrar nuevo cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formNuevoCliente" autocomplete="off">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nc_nombre">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nc_nombre" name="nombre_cliente"
                                       maxlength="255" placeholder="Nombre completo del cliente">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nc_nit_ci">NIT/CI <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nc_nit_ci" name="nit_ci_cliente"
                                       maxlength="50" placeholder="Número de NIT o CI">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nc_celular">Celular <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nc_celular" name="celular_cliente"
                                       maxlength="50" placeholder="Número de celular">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nc_email">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="nc_email" name="email_cliente"
                                       maxlength="254" placeholder="correo@ejemplo.com">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" id="btnNuevoCliente">
                        <i class="fas fa-check"></i> Crear y seleccionar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- /Modal nuevo cliente -->

<!-- Form oculto para agregar al carrito -->
<form id="formCarrito" action="<?= BASE_URL ?>/sales/cart/add" method="post" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="nro_venta" value="<?= (int)$nro_venta ?>">
    <input type="hidden" name="id_producto" id="cart_id_producto">
    <input type="hidden" name="cantidad" id="cart_cantidad">
</form>
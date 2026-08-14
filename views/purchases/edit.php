<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Editar compra</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/purchases"><i
                                        class="fas fa-shopping-cart"></i> Compras</a></li>
                        <li class="breadcrumb-item active">Editar compra</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <form id="purchaseEditForm" action="<?= BASE_URL ?>/purchases/update" method="post">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="id_compra" value="<?= $id_compra; ?>">
                <input type="hidden" name="old_id_producto" value="<?= $old_id_producto; ?>">
                <input type="hidden" name="old_cantidad" value="<?= $old_cantidad; ?>">

                <div class="row">
                    <!-- ============================================ -->
                    <!-- COLUMNA IZQUIERDA: Campos del formulario     -->
                    <!-- ============================================ -->
                    <div class="col-md-8">

                        <!-- Card 1: Encabezado -->
                        <div class="card">
                            <div class="card-header card-outline card-success">
                                <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i> Encabezado de la
                                    compra</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>N° Compra</label>
                                            <input type="number" name="nro_compra" class="form-control"
                                                   value="<?= htmlspecialchars($nro_compra, ENT_QUOTES, 'UTF-8'); ?>"
                                                   readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Fecha de compra <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <button type="button" class="input-group-text"
                                                            data-toggle="date-picker"
                                                            aria-label="Abrir selector de fecha"
                                                            data-target="fecha_compra">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </button>
                                                </div>
                                                <input type="date" id="fecha_compra" name="fecha_compra"
                                                       class="form-control" required
                                                       value="<?= htmlspecialchars($fecha_compra, ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Comprobante <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                                                </div>
                                                <input type="text" name="comprobante" class="form-control" required
                                                       value="<?= htmlspecialchars($comprobante, ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card Encabezado -->

                        <!-- Card 2: Proveedor y Producto -->
                        <div class="card">
                            <div class="card-header card-outline card-success">
                                <h3 class="card-title"><i class="fas fa-boxes mr-1"></i> Proveedor y Producto</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_producto">Producto <span class="text-danger">*</span></label>
                                            <select id="id_producto" name="id_producto" class="form-control select2"
                                                    required>
                                                <?php foreach ($products as $product) : ?>
                                                    <option value="<?= $product['id_producto']; ?>"
                                                            <?= $product['id_producto'] == $id_producto ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($product['codigo'] . ' — ' . $product['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_proveedor">Proveedor <span
                                                        class="text-danger">*</span></label>
                                            <select id="id_proveedor" name="id_proveedor" class="form-control select2"
                                                    required>
                                                <?php foreach ($suppliers as $supplier) : ?>
                                                    <option value="<?= $supplier['id_proveedor']; ?>"
                                                            <?= $supplier['id_proveedor'] == $id_proveedor ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($supplier['nombre_proveedor'] . ' — ' . ($supplier['empresa'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card Proveedor y Producto -->

                        <!-- Card 3: Precio y Cantidad -->
                        <div class="card">
                            <div class="card-header card-outline card-success">
                                <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> Precio y Cantidad</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="precio_compra">Precio de compra <span
                                                        class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">$</span>
                                                </div>
                                                <input type="number" id="precio_compra" name="precio_compra"
                                                       class="form-control" step="0.01" min="0" required
                                                       value="<?= htmlspecialchars($precio_compra, ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="cantidad">Cantidad <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i
                                                                class="fas fa-sort-numeric-up-alt"></i></span>
                                                </div>
                                                <input type="number" id="cantidad" name="cantidad" class="form-control"
                                                       min="1" required
                                                       value="<?= htmlspecialchars($cantidad, ENT_QUOTES, 'UTF-8'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Usuario</label>
                                            <input type="text" aria-label="usuario"
                                                   value="<?= htmlspecialchars($email_sesion, ENT_QUOTES, 'UTF-8'); ?>"
                                                   class="form-control" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL ?>/purchases" class="btn btn-default w-100">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-save"></i> Actualizar compra
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /.card Precio y Cantidad -->

                    </div><!-- /.col-md-8 -->

                    <!-- ============================================ -->
                    <!-- COLUMNA DERECHA: Sidebar sticky              -->
                    <!-- ============================================ -->
                    <div class="col-md-4">
                        <div class="purchase-sidebar-sticky">

                            <!-- Panel: Resumen de compra -->
                            <div class="card" id="resumenCard">
                                <div class="card-header card-outline card-info">
                                    <h3 class="card-title"><i class="fas fa-receipt mr-1"></i> Resumen de compra</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-borderless mb-0">
                                        <tbody>
                                        <tr>
                                            <td class="text-muted">Producto</td>
                                            <td class="text-right font-weight-bold" id="resumenProducto">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Proveedor</td>
                                            <td class="text-right font-weight-bold" id="resumenProveedor">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Precio unitario</td>
                                            <td class="text-right font-weight-bold" id="resumenPrecio">$ 0.00</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Cantidad</td>
                                            <td class="text-right font-weight-bold" id="resumenCantidad">0</td>
                                        </tr>
                                        <tr class="border-top resumen-total">
                                            <td class="text-muted">Total</td>
                                            <td class="text-right">
                                                <span class="badge badge-success" id="badgeTotal">$ 0.00</span>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- /.card Resumen -->

                        </div><!-- /.purchase-sidebar-sticky -->
                    </div><!-- /.col-md-4 -->

                </div><!-- /.row -->
            </form>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->

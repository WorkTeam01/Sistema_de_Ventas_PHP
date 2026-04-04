<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Editar compra</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/purchases"><i class="fas fa-shopping-cart"></i> Compras</a></li>
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
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Modificar datos de la compra</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <form id="purchaseEditForm" action="<?= BASE_URL ?>/purchases/update" method="post">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="id_compra" value="<?= $id_compra; ?>">
                                <input type="hidden" name="old_id_producto" value="<?= $old_id_producto; ?>">
                                <input type="hidden" name="old_cantidad" value="<?= $old_cantidad; ?>">
                                <!-- Fila 1: Nro Compra, Fecha, Comprobante -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>N° Compra</label>
                                            <input type="number" name="nro_compra" class="form-control"
                                                value="<?= htmlspecialchars($nro_compra, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Fecha de compra <span class="text-danger">*</span></label>
                                            <input type="date" name="fecha_compra" class="form-control"
                                                value="<?= htmlspecialchars($fecha_compra, ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Comprobante <span class="text-danger">*</span></label>
                                            <input type="text" name="comprobante" class="form-control"
                                                value="<?= htmlspecialchars($comprobante, ENT_QUOTES, 'UTF-8'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <!-- Fila 2: Producto, Proveedor -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Producto <span class="text-danger">*</span></label>
                                            <select name="id_producto" class="form-control" required>
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
                                            <label>Proveedor <span class="text-danger">*</span></label>
                                            <select name="id_proveedor" class="form-control" required>
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
                                <!-- Fila 3: Precio, Cantidad, Usuario -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Precio de compra <span class="text-danger">*</span></label>
                                            <input type="number" name="precio_compra" class="form-control"
                                                value="<?= htmlspecialchars($precio_compra, ENT_QUOTES, 'UTF-8'); ?>" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Cantidad <span class="text-danger">*</span></label>
                                            <input type="number" name="cantidad" class="form-control"
                                                value="<?= htmlspecialchars($cantidad, ENT_QUOTES, 'UTF-8'); ?>" min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Usuario</label>
                                            <input type="text" value="<?= htmlspecialchars($email_sesion, ENT_QUOTES, 'UTF-8'); ?>" class="form-control" disabled>
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
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Actualizar compra
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->

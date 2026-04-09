<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalle de compra</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/purchases"><i
                                        class="fas fa-shopping-cart"></i> Compras</a></li>
                        <li class="breadcrumb-item active">Detalle #<?= $nro_compra ?></li>
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

                <!-- Columna izquierda: imagen del producto -->
                <div class="col-md-3">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Producto</h3>
                        </div>
                        <div class="card-body text-center">
                            <img src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8'); ?>"
                                 class="img-thumbnail img-fluid mb-3" width="100%"
                                 alt="<?= htmlspecialchars($nombre_producto, ENT_QUOTES, 'UTF-8'); ?>">
                            <p class="text-muted mb-1">
                                <strong><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></strong></p>
                            <p class="mb-1"><?= htmlspecialchars($nombre_producto, ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="badge badge-secondary"><?= htmlspecialchars($nombre_categoria, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="card-footer">
                            <a href="<?= BASE_URL ?>/purchases/report/<?= $id_compra ?>"
                               class="btn btn-info btn-block" target="_blank">
                                <i class="fas fa-file-pdf"></i> Reporte PDF
                            </a>
                            <a href="<?= BASE_URL ?>/purchases/edit/<?= $id_compra ?>"
                               class="btn btn-success btn-block mt-2">
                                <i class="fas fa-pencil-alt"></i> Editar
                            </a>
                            <a href="<?= BASE_URL ?>/purchases" class="btn btn-default btn-block mt-2">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: datos de la compra -->
                <div class="col-md-9">

                    <!-- Datos de la compra -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shopping-cart mr-1"></i> Compra
                                N° <?= htmlspecialchars($nro_compra, ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered mb-0">
                                <tbody>
                                <tr>
                                    <th width="35%">Comprobante</th>
                                    <td><?= htmlspecialchars($comprobante, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <th>Fecha de compra</th>
                                    <td><?= htmlspecialchars($fecha_compra, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <th>Precio de compra</th>
                                    <td><?= htmlspecialchars($precio_compra, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <th>Cantidad</th>
                                    <td><?= htmlspecialchars($cantidad, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <th>Total</th>
                                    <td><strong><?= number_format($precio_compra * $cantidad, 2); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Registrado por</th>
                                    <td><?= htmlspecialchars($email_usuario, ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Datos del proveedor -->
                        <div class="col-md-6">
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-truck mr-1"></i> Proveedor</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                        <tr>
                                            <th width="40%">Nombre</th>
                                            <td><?= htmlspecialchars($nombre_proveedor, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Empresa</th>
                                            <td><?= htmlspecialchars($empresa ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td><?= htmlspecialchars($email_proveedor ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Celular</th>
                                            <td><?= htmlspecialchars($celular ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Teléfono</th>
                                            <td><?= htmlspecialchars($telefono ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Dirección</th>
                                            <td><?= htmlspecialchars($direccion ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del producto -->
                        <div class="col-md-6">
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-boxes mr-1"></i> Stock actual</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                        <tr>
                                            <th width="50%">Descripción</th>
                                            <td><?= htmlspecialchars($descripcion_producto ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Stock actual</th>
                                            <td><?= htmlspecialchars($stock, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Precio venta</th>
                                            <td><?= htmlspecialchars($precio_venta, ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Auditoría -->
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-history mr-1"></i> Auditoría</h3>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                        <tr>
                                            <th width="50%">Creado el</th>
                                            <td><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Actualizado el</th>
                                            <td><?= htmlspecialchars($fyh_actualizacion ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
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

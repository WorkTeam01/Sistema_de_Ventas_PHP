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
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/purchases">
                                <i class="fas fa-shopping-cart"></i> Compras</a>
                        </li>
                        <li class="breadcrumb-item active">Detalle #<?= htmlspecialchars($nro_compra, ENT_QUOTES, 'UTF-8'); ?></li>
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

                <!-- Columna izquierda: imagen y acciones -->
                <div class="col-md-4 col-lg-3">
                    <div class="card card-outline card-info">
                        <div class="card-body text-center pb-2">
                            <img src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8'); ?>"
                                class="img-fluid rounded mb-3"
                                style="max-height:220px; object-fit:contain;"
                                alt="<?= htmlspecialchars($nombre_producto, ENT_QUOTES, 'UTF-8'); ?>">
                            <h5 class="font-weight-bold mb-1"><?= htmlspecialchars($nombre_producto, ENT_QUOTES, 'UTF-8'); ?></h5>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="badge badge-info badge-pill px-3 py-1"><?= htmlspecialchars($nombre_categoria, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="card-footer d-flex flex-column">
                            <a href="<?= BASE_URL ?>/purchases/report/<?= $id_compra ?>"
                                class="btn btn-info btn-block" target="_blank">
                                <i class="fas fa-file-pdf mr-1"></i> Reporte PDF
                            </a>
                            <a href="<?= BASE_URL ?>/purchases/edit/<?= $id_compra ?>"
                                class="btn btn-success btn-block mt-2">
                                <i class="fas fa-pencil-alt mr-1"></i> Editar
                            </a>
                            <a href="<?= BASE_URL ?>/purchases" class="btn btn-default btn-block mt-2">
                                <i class="fas fa-arrow-left mr-1"></i> Volver al listado
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Columna derecha: datos y métricas -->
                <div class="col-md-8 col-lg-9">

                    <!-- Tarjetas de métricas -->
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="info-box shadow-sm">
                                <span class="info-box-icon bg-warning"><i class="fas fa-dollar-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Precio de compra</span>
                                    <span class="info-box-number"><?= APP_CURRENCY_SYMBOL ?> <?= htmlspecialchars($precio_compra, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="info-box shadow-sm">
                                <span class="info-box-icon bg-info"><i class="fas fa-cubes"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Cantidad comprada</span>
                                    <span class="info-box-number"><?= htmlspecialchars($cantidad, ENT_QUOTES, 'UTF-8'); ?> uds.</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="info-box shadow-sm">
                                <span class="info-box-icon bg-success"><i class="fas fa-receipt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total</span>
                                    <span class="info-box-number"><?= APP_CURRENCY_SYMBOL ?> <?= number_format($precio_compra * $cantidad, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="card card-outline card-outline-tabs card-info">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="purchaseTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="tab-detalles" data-toggle="pill"
                                        href="#pill-detalles" role="tab">
                                        <i class="fas fa-info-circle mr-1"></i> Detalles
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-proveedor" data-toggle="pill"
                                        href="#pill-proveedor" role="tab">
                                        <i class="fas fa-truck mr-1"></i> Proveedor
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-producto" data-toggle="pill"
                                        href="#pill-producto" role="tab">
                                        <i class="fas fa-boxes mr-1"></i> Producto
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="purchaseTabsContent">

                                <!-- Tab: Detalles -->
                                <div class="tab-pane fade show active" id="pill-detalles" role="tabpanel">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 text-muted">N° de compra</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($nro_compra, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Comprobante</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($comprobante, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Fecha de compra</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($fecha_compra, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Registrado por</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($email_usuario, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Creado el</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Actualizado el</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($fyh_actualizacion ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                                    </dl>
                                </div>

                                <!-- Tab: Proveedor -->
                                <div class="tab-pane fade" id="pill-proveedor" role="tabpanel">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 text-muted">Nombre</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($nombre_proveedor, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Empresa</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($empresa ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Email</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($email_proveedor ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Celular</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($celular ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Teléfono</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($telefono ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Dirección</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($direccion ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                                    </dl>
                                </div>

                                <!-- Tab: Producto -->
                                <div class="tab-pane fade" id="pill-producto" role="tabpanel">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 text-muted">Descripción</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($descripcion_producto ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Precio venta</dt>
                                        <dd class="col-sm-8"><?= APP_CURRENCY_SYMBOL ?> <?= htmlspecialchars($precio_venta, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Stock actual</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($stock, ENT_QUOTES, 'UTF-8'); ?> uds.</dd>
                                    </dl>
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
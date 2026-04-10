<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalle del producto</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/products"><i class="fas fa-warehouse"></i>
                                Almacén</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
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
                                 alt="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
                            <h5 class="font-weight-bold mb-1"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></h5>
                            <p class="text-muted small mb-2"><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="badge badge-info badge-pill px-3 py-1"><?= htmlspecialchars($nombre_categoria, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="card-footer d-flex flex-column gap-2">
                            <a href="<?= BASE_URL ?>/products/edit/<?= $id_producto ?>"
                               class="btn btn-success btn-block">
                                <i class="fas fa-pencil-alt mr-1"></i> Editar producto
                            </a>
                            <a href="<?= BASE_URL ?>/products" class="btn btn-default btn-block mt-2">
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
                                <span class="info-box-icon bg-success"><i class="fas fa-tag"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Precio de venta</span>
                                    <span class="info-box-number">Bs <?= htmlspecialchars($precio_venta, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="info-box shadow-sm">
                                <span class="info-box-icon bg-warning"><i class="fas fa-dollar-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Precio de compra</span>
                                    <span class="info-box-number">Bs <?= htmlspecialchars($precio_compra, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <?php
                            $s = (int)$stock;
                            $sn = (int)$stock_minimo;
                            $sx = (int)$stock_maximo;
                            if ($s < $sn) {
                                $stockColor = 'bg-danger';
                                $stockLabel = 'Stock actual (bajo mínimo)';
                            } elseif ($sx > 0 && $s > $sx) {
                                $stockColor = 'bg-primary';
                                $stockLabel = 'Stock actual (sobre máximo)';
                            } else {
                                $stockColor = 'bg-info';
                                $stockLabel = 'Stock actual';
                            }
                            ?>
                            <div class="info-box shadow-sm">
                                <span class="info-box-icon <?= $stockColor ?>"><i class="fas fa-boxes"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text"><?= $stockLabel ?></span>
                                    <span class="info-box-number"><?= $s ?> uds.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="card card-outline card-outline-tabs card-info">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="tab-detalles" data-toggle="pill"
                                       href="#pill-detalles" role="tab">
                                        <i class="fas fa-info-circle mr-1"></i> Detalles
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-stock" data-toggle="pill"
                                       href="#pill-stock" role="tab">
                                        <i class="fas fa-chart-bar mr-1"></i> Nivel de stock
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="productTabsContent">

                                <!-- Tab: Detalles -->
                                <div class="tab-pane fade show active" id="pill-detalles" role="tabpanel">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 text-muted">Código</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Nombre</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Categoría</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge badge-info"><?= htmlspecialchars($nombre_categoria, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </dd>

                                        <dt class="col-sm-4 text-muted">Descripción</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($descripcion ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Fecha de ingreso</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($fecha_ingreso, ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Creado el</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>

                                        <dt class="col-sm-4 text-muted">Actualizado el</dt>
                                        <dd class="col-sm-8"><?= htmlspecialchars($fyh_actualizacion ?? '—', ENT_QUOTES, 'UTF-8'); ?></dd>
                                    </dl>
                                </div>

                                <!-- Tab: Nivel de stock -->
                                <div class="tab-pane fade" id="pill-stock" role="tabpanel">
                                    <?php
                                        $pct = ($sx > 0) ? min(round(($s / $sx) * 100, 1), 100) : 0;
                                        if ($s < $sn) {
                                            $barColor   = 'bg-danger';
                                            $barLabel   = 'bajo mínimo';
                                        } elseif ($sx > 0 && $s > $sx) {
                                            $barColor   = 'bg-primary';
                                            $barLabel   = 'sobre máximo';
                                            $pct        = 100;
                                        } else {
                                            $barColor   = 'bg-success';
                                            $barLabel   = 'normal';
                                        }
                                    ?>
                                    <dl class="row mb-3">
                                        <dt class="col-sm-4 text-muted">Stock mínimo</dt>
                                        <dd class="col-sm-8"><?= $sn ?> uds.</dd>

                                        <dt class="col-sm-4 text-muted">Stock máximo</dt>
                                        <dd class="col-sm-8"><?= $sx > 0 ? $sx . ' uds.' : '—'; ?></dd>

                                        <dt class="col-sm-4 text-muted">Stock actual</dt>
                                        <dd class="col-sm-8">
                                            <?php if ($s < $sn): ?>
                                                <span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i><?= $s ?> uds. — bajo mínimo</span>
                                            <?php elseif ($sx > 0 && $s > $sx): ?>
                                                <span class="badge badge-primary"><i class="fas fa-arrow-up mr-1"></i><?= $s ?> uds. — sobre máximo</span>
                                            <?php else: ?>
                                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i><?= $s ?> uds.</span>
                                            <?php endif; ?>
                                        </dd>
                                    </dl>

                                    <?php if ($sx > 0): ?>
                                    <div class="d-flex justify-content-between text-muted small mb-1">
                                        <span><?= $s ?> / <?= $sx ?> unidades</span>
                                        <span><?= $pct ?>%</span>
                                    </div>
                                    <div class="progress" style="height:14px;">
                                        <div class="progress-bar <?= $barColor ?>"
                                             role="progressbar"
                                             style="width: <?= $pct ?>%"
                                             aria-valuenow="<?= $s ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="<?= $sx ?>">
                                        </div>
                                    </div>
                                    <p class="text-muted small mt-1 mb-0">Estado: <?= $barLabel ?></p>
                                    <?php else: ?>
                                    <p class="text-muted small mb-0">No se ha definido un stock máximo para calcular el nivel.</p>
                                    <?php endif; ?>
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
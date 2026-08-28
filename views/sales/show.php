<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalle de Venta</h1>
                </div>
                <div class="col-sm-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                            </li>
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/sales"><i class="fas fa-shopping-cart"></i>
                                    Ventas</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Detalle</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">

            <!-- Acciones + título -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="fas fa-receipt text-info mr-2"></i>
                                Venta <span class="text-info">N° <?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></span>
                                <small class="text-muted d-none d-sm-inline ml-2"><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8') ?></small>
                            </h2>
                            <div class="card-tools">
                                <a href="<?= BASE_URL ?>/sales/invoice/<?= (int)$id_venta ?>" target="_blank"
                                    class="btn btn-success btn-sm" aria-label="Imprimir factura">
                                    <i class="fas fa-print" aria-hidden="true"></i><span class="d-none d-sm-inline ml-1"> Imprimir factura</span>
                                </a>
                                <a href="<?= BASE_URL ?>/sales/delete/<?= (int)$id_venta ?>"
                                    class="btn btn-danger btn-sm ml-1" aria-label="Eliminar venta">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i><span class="d-none d-sm-inline ml-1"> Eliminar</span>
                                </a>
                                <a href="<?= BASE_URL ?>/sales" class="btn btn-default btn-sm ml-1" aria-label="Volver al listado de ventas">
                                    <i class="fas fa-arrow-left" aria-hidden="true"></i><span class="d-none d-sm-inline ml-1"> Volver</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cabecera: datos de la venta + datos del cliente -->
            <div class="row">
                <!-- Datos de la venta -->
                <div class="col-md-6">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-file-invoice mr-1 text-info"></i> Datos de la venta
                            </h2>
                        </div>
                        <div class="card-body pb-2">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">N° Venta</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></dd>

                                <dt class="col-sm-5 text-muted">Total pagado</dt>
                                <dd class="col-sm-7">
                                    <span class="badge badge-warning px-2 py-1" style="font-size:.85rem;">
                                        <?= APP_CURRENCY_SYMBOL ?> <?= htmlspecialchars(number_format((float)$total_pagado, 2), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </dd>

                                <dt class="col-sm-5 text-muted">Fecha de creación</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                                <dt class="col-sm-5 text-muted">Última actualización</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($fyh_actualizacion ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Datos del cliente -->
                <div class="col-md-6">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title text-muted"><i class="fas fa-user mr-1"></i> Cliente</h3>
                        </div>
                        <div class="card-body pb-2">
                            <dl class="row mb-0">
                                <dt class="col-sm-4 text-muted">Nombre</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8') ?></dd>

                                <dt class="col-sm-4 text-muted">NIT/CI</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($nit_ci_cliente, ENT_QUOTES, 'UTF-8') ?></dd>

                                <dt class="col-sm-4 text-muted">Celular</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($celular_cliente ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                                <dt class="col-sm-4 text-muted">Email</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($email_cliente ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de ítems -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-shopping-bag mr-1"></i> Productos de la venta</h2>
                            <div class="card-tools">
                                <span class="badge badge-primary"><?= $total_productos ?> producto<?= $total_productos !== 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="bg-secondary text-white">
                                        <tr class="text-center">
                                            <th style="width:50px" scope="col"><span class="sr-only">Imagen</span></th>
                                            <th class="text-left">Producto</th>
                                            <th class="text-left d-none d-md-table-cell">Descripción</th>
                                            <th>Cantidad</th>
                                            <th class="d-none d-md-table-cell">Precio unitario</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($items)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">No hay productos
                                                    registrados en esta venta.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($items as $item): ?>
                                                <tr>
                                                    <td class="text-center align-middle">
                                                        <img src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($item['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                                            alt="<?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                            style="width:38px; height:38px; object-fit:contain;" loading="lazy">
                                                    </td>
                                                    <td class="align-middle">
                                                        <span class="font-weight-bold"><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></span><br>
                                                        <small class="text-muted"><?= htmlspecialchars($item['codigo'], ENT_QUOTES, 'UTF-8') ?></small>
                                                    </td>
                                                    <td class="align-middle text-muted small d-none d-md-table-cell"><?= htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-center align-middle"><?= (int)$item['cantidad'] ?></td>
                                                    <td class="text-center align-middle d-none d-md-table-cell">
                                                        <?= APP_CURRENCY_SYMBOL ?> <?= htmlspecialchars(number_format((float)$item['precio_venta'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-center align-middle">
                                                        <?= APP_CURRENCY_SYMBOL ?> <?= number_format($item['subtotal'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if (!empty($items)): ?>
                                        <tfoot>
                                            <tr class="bg-light">
                                                <td colspan="2" class="text-right font-weight-bold pr-3">
                                                    <?= $total_productos ?> producto<?= $total_productos !== 1 ? 's' : '' ?>
                                                </td>
                                                <td class="d-none d-md-table-cell"></td>
                                                <td class="text-center font-weight-bold"><?= $cantidad_acum ?> uds.</td>
                                                <td class="text-right font-weight-bold d-none d-md-table-cell">
                                                    Total:
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-warning px-2 py-1" style="font-size:.9rem;">
                                                        <?= APP_CURRENCY_SYMBOL ?> <?= number_format($subtotal_acum, 2) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
</section>
<!-- /.content-wrapper -->
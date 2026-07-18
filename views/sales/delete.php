<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Eliminar Venta</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/sales"><i class="fas fa-shopping-cart"></i>
                                Ventas</a></li>
                        <li class="breadcrumb-item active">Eliminar</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">

            <!-- Advertencia -->
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-exclamation-triangle"></i> Atención</h5>
                        Está a punto de eliminar la <strong>Venta N° <?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></strong>.
                        Esta acción revertirá el stock de todos los productos del carrito.
                        <strong>Esta operación no se puede deshacer.</strong>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Detalle de la venta -->
                <div class="col-md-8">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-receipt mr-1"></i> Venta N° <?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></h3>
                        </div>
                        <div class="card-body pb-2">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5 text-muted">N° Venta</dt>
                                        <dd class="col-sm-7"><?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?></dd>

                                        <dt class="col-sm-5 text-muted">Total pagado</dt>
                                        <dd class="col-sm-7">
                                            <span class="badge badge-warning px-2 py-1" style="font-size:.85rem;">
                                                Bs. <?= htmlspecialchars(number_format((float)$total_pagado, 2), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </dd>

                                        <dt class="col-sm-5 text-muted">Fecha</dt>
                                        <dd class="col-sm-7"><?= htmlspecialchars($fyh_creacion ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-5 text-muted">Cliente</dt>
                                        <dd class="col-sm-7"><?= htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8') ?></dd>

                                        <dt class="col-sm-5 text-muted">NIT/CI</dt>
                                        <dd class="col-sm-7"><?= htmlspecialchars($nit_ci_cliente, ENT_QUOTES, 'UTF-8') ?></dd>
                                    </dl>
                                </div>
                            </div>

                            <!-- Ítems del carrito -->
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="bg-secondary text-white">
                                        <tr class="text-center">
                                            <th style="width:50px"></th>
                                            <th class="text-left">Producto</th>
                                            <th class="text-left d-none d-md-table-cell">Descripción</th>
                                            <th>Cantidad</th>
                                            <th class="d-none d-md-table-cell">Precio unitario</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $subtotal_acum = 0.0;
                                        $cantidad_acum = 0;
                                        $total_productos = count($items);
                                        foreach ($items as $item):
                                            $subtotal       = (float)$item['cantidad'] * (float)$item['precio_venta'];
                                            $subtotal_acum += $subtotal;
                                            $cantidad_acum += (int)$item['cantidad'];
                                        ?>
                                            <tr>
                                                <td class="text-center align-middle">
                                                    <img src="<?= BASE_URL . '/uploads/products/' . htmlspecialchars($item['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                                        alt="<?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                        style="width:38px; height:38px; object-fit:contain;">
                                                </td>
                                                <td class="align-middle">
                                                    <span class="font-weight-bold"><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></span><br>
                                                    <small class="text-muted"><?= htmlspecialchars($item['codigo'], ENT_QUOTES, 'UTF-8') ?></small>
                                                </td>
                                                <td class="align-middle text-muted small d-none d-md-table-cell"><?= htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center align-middle"><?= (int)$item['cantidad'] ?></td>
                                                <td class="text-center align-middle d-none d-md-table-cell">Bs. <?= htmlspecialchars(number_format((float)$item['precio_venta'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center align-middle">Bs. <?= number_format($subtotal, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <?php if (!empty($items)): ?>
                                        <tfoot>
                                            <tr class="bg-light">
                                                <td colspan="2" class="text-right font-weight-bold pr-3 text-muted">
                                                    <?= $total_productos ?> producto<?= $total_productos !== 1 ? 's' : '' ?>
                                                </td>
                                                <td class="d-none d-md-table-cell"></td>
                                                <td class="text-center font-weight-bold"><?= $cantidad_acum ?> uds.</td>
                                                <td class="text-right font-weight-bold text-muted d-none d-md-table-cell">Total:</td>
                                                <td class="text-center">
                                                    <span class="badge badge-warning px-2 py-1" style="font-size:.9rem;">
                                                        Bs. <?= number_format($subtotal_acum, 2) ?>
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

                <!-- Panel de confirmación -->
                <div class="col-md-4">
                    <div class="card card-outline card-danger">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-exclamation-circle mr-1"></i> Confirmar eliminación</h3>
                        </div>
                        <div class="card-body">
                            <p>Al confirmar, se realizarán las siguientes operaciones de forma irreversible:</p>
                            <ul>
                                <li>Se eliminará el registro de la venta.</li>
                                <li>Se eliminarán los ítems del carrito asociados.</li>
                                <li>El stock de cada producto será restaurado.</li>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <form id="formEliminar" action="<?= BASE_URL ?>/sales/delete" method="post">
                                <input type="hidden" name="csrf_token"
                                    value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id_venta" value="<?= (int)$id_venta ?>">
                                <div class="d-flex flex-column">
                                    <a href="<?= BASE_URL ?>/sales" class="btn btn-default mb-2 w-100">
                                        <i class="fas fa-times mr-1"></i> Cancelar
                                    </a>
                                    <button type="button" class="btn btn-danger w-100 btn-confirm-delete-sale"
                                        data-nro-venta="<?= htmlspecialchars($nro_venta, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-trash mr-1"></i> Confirmar eliminación
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div>
</section>
<!-- /.content-wrapper -->
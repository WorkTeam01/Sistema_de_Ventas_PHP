<section class="content-wrapper">

    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1>Devolución Nro <?= (int)$return['nro_devolucion'] ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/returns">Devoluciones</a></li>
                        <li class="breadcrumb-item active">Detalle</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4 col-lg-3">
                    <div class="card card-outline card-warning">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <i class="fas fa-undo fa-3x text-warning" aria-hidden="true"></i>
                            </div>
                            <h2 class="profile-username text-center">Devolución Nro <?= (int)$return['nro_devolucion'] ?></h2>
                            <p class="text-muted text-center">
                                Venta Nro <?= (int)$return['nro_venta'] ?>
                            </p>
                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Fecha</b> <span class="float-right"><?= htmlspecialchars($return['fyh_creacion'], ENT_QUOTES, 'UTF-8') ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b>Monto</b> <a class="float-right font-weight-bold monto-preview">
                                        <?= APP_CURRENCY_SYMBOL ?> <?= number_format((float)$return['monto'], 2) ?>
                                    </a>
                                </li>
                                <li class="list-group-item">
                                    <b>Registrado por</b> <span class="float-right">
                                        <?= htmlspecialchars($return['usuario_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </li>
                            </ul>
                            <a href="<?= BASE_URL ?>/returns" class="btn btn-secondary btn-block">
                                <i class="fas fa-arrow-left mr-1"></i> Volver al listado
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-8 col-lg-9">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-shopping-cart mr-1"></i> Datos de la venta</h2>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">N° Venta</dt>
                                <dd class="col-sm-8"><?= (int)$return['nro_venta'] ?></dd>

                                <dt class="col-sm-4">Cliente</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($return['nombre_cliente'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                                <dt class="col-sm-4">NIT/CI</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($return['nit_ci_cliente'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                                <dt class="col-sm-4">Total original</dt>
                                <dd class="col-sm-8"><?= APP_CURRENCY_SYMBOL ?> <?= number_format((float)$return['total_pagado'], 2) ?></dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-list mr-1"></i> Ítems devueltos</h2>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="bg-secondary text-white">
                                        <tr class="text-center">
                                            <th>Producto</th>
                                            <th>Código</th>
                                            <th>Cantidad</th>
                                            <th>Precio unitario</th>
                                            <th class="text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($return['items'] as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($item['nombre_producto'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center">
                                                    <?= htmlspecialchars($item['codigo'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="text-center font-weight-bold"><?= (int)$item['cantidad'] ?> uds.</td>
                                                <td class="text-right">
                                                    <?= APP_CURRENCY_SYMBOL ?> <?= number_format((float)$item['precio_unitario'], 2) ?>
                                                </td>
                                                <td class="text-right">
                                                    <?= APP_CURRENCY_SYMBOL ?> <?= number_format((float)$item['cantidad'] * (float)$item['precio_unitario'], 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <td colspan="4" class="text-right font-weight-bold">Total devuelto:</td>
                                            <td class="text-right font-weight-bold monto-preview">
                                                <?= APP_CURRENCY_SYMBOL ?> <?= number_format((float)$return['monto'], 2) ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-comment mr-1"></i> Motivo</h2>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><?= nl2br(htmlspecialchars($return['motivo'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>
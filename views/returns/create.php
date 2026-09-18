<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Registrar devolución — Venta Nro <?= (int)$nro_venta ?></h1>
                </div>
                <div class="col-sm-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/returns"><i class="fas fa-undo"></i> Devoluciones</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Registrar</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <form id="formReturn" method="POST" action="<?= BASE_URL ?>/returns">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id_venta" value="<?= (int)$id_venta ?>">
                        <input type="hidden" name="id_usuario_venta" value="<?= (int)$id_usuario ?>">

                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h2 class="card-title"><i class="fas fa-shopping-cart mr-1"></i> Ítems de la venta</h2>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($items)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-check-circle fa-2x mb-2 text-success" aria-hidden="true"></i>
                                        <p class="mb-0">Todos los ítems ya fueron devueltos.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="bg-secondary text-white">
                                                <tr class="text-center">
                                                    <th>Producto</th>
                                                    <th>Vendida</th>
                                                    <th>Pendiente</th>
                                                    <th>Precio</th>
                                                    <th>Cantidad a devolver</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['nombre_producto'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td class="text-center"><?= (int)$item['vendida'] ?></td>
                                                        <td class="text-center">
                                                            <span class="badge badge-warning pendiente"
                                                                data-product-id="<?= (int)$item['id_producto'] ?>"
                                                                data-precio="<?= htmlspecialchars($item['precio_unitario'], ENT_QUOTES, 'UTF-8') ?>">
                                                                <?= (int)$item['pendiente'] ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-right">
                                                            <?= APP_CURRENCY_SYMBOL ?> <?= number_format((float)$item['precio_unitario'], 2) ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <input type="number" name="qty_<?= (int)$item['id_producto'] ?>"
                                                                class="form-control d-inline-block text-center qty-input"
                                                                min="0" max="<?= (int)$item['pendiente'] ?>"
                                                                value="0"
                                                                data-product-id="<?= (int)$item['id_producto'] ?>"
                                                                aria-label="Cantidad a devolver de <?= htmlspecialchars($item['nombre_producto'], ENT_QUOTES, 'UTF-8') ?>">
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h2 class="card-title"><i class="fas fa-comment mr-1"></i> Motivo de la devolución</h2>
                            </div>
                            <div class="card-body">
                                <textarea name="motivo" id="motivo" class="form-control" rows="3"
                                    placeholder="Describa el motivo de la devolución (mínimo 10 caracteres)..." required
                                    minlength="10"
                                    aria-label="Motivo de la devolución" aria-describedby="motivoHelp"></textarea>
                                <small id="motivoHelp" class="form-text text-muted">
                                    <span id="motivoCount">0</span>/400 caracteres (mínimo 10)
                                </small>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-4">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-info-circle mr-1"></i> Resumen</h2>
                        </div>
                        <div class="card-body">
                            <dl>
                                <dt>N° Venta</dt>
                                <dd><?= (int)$nro_venta ?></dd>
                                <dt>Cliente</dt>
                                <dd><?= htmlspecialchars($nombre_cliente ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                                <dt>Fecha de venta</dt>
                                <dd><?= htmlspecialchars($fyh_creacion, ENT_QUOTES, 'UTF-8') ?></dd>
                            </dl>
                            <hr>
                            <p class="mb-1"><strong>Monto devuelto:</strong></p>
                            <p id="montoPreview" class="font-weight-bold monto-preview">
                                <?= APP_CURRENCY_SYMBOL ?> 0.00
                            </p>
                        </div>
                    </div>

                    <div class="card card-outline card-warning">
                        <div class="card-body">
                            <a href="<?= BASE_URL ?>/returns" class="btn btn-secondary btn-block">
                                <i class="fas fa-arrow-left mr-1"></i> Cancelar
                            </a>
                            <button type="submit" form="formReturn" class="btn btn-warning btn-block" id="btnRegistrar" disabled>
                                <i class="fas fa-save mr-1"></i> Registrar devolución
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->
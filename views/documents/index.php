<section class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h1><?= $type === 'cotizacion' ? 'Cotizaciones' : 'Órdenes de compra' ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Inicio</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title">Documentos registrados</h2>
                    <?php if ($canManage): ?>
                        <a class="btn btn-primary btn-sm ml-auto"
                            href="<?= BASE_URL ?>/documents/create?tipo=<?= rawurlencode($type) ?>">
                            <i class="fas fa-plus mr-1"></i> Crear <?= htmlspecialchars(mb_strtolower($typeLabel), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="btn-group mb-3" role="group" aria-label="Tipo de documento">
                        <a class="btn <?= $type === 'cotizacion' ? 'btn-primary' : 'btn-outline-primary' ?>"
                            href="<?= BASE_URL ?>/documents?tipo=cotizacion">Cotizaciones</a>
                        <a class="btn <?= $type === 'orden_compra' ? 'btn-primary' : 'btn-outline-primary' ?>"
                            href="<?= BASE_URL ?>/documents?tipo=orden_compra">Órdenes de compra</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Fecha</th>
                                    <th><?= $type === 'cotizacion' ? 'Cliente' : 'Proveedor' ?></th>
                                    <th>Estado</th>
                                    <th class="text-right">Total</th>
                                    <th>Registrado por</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $row): ?>
                                    <?php $prefix = $type === 'cotizacion' ? 'COT' : 'OC'; ?>
                                    <tr>
                                        <td><?= $prefix ?>-<?= str_pad((string)$row['id_documento'], 6, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars($row['fecha_documento'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($type === 'cotizacion' ? ($row['nombre_cliente'] ?? '—') : ($row['nombre_proveedor'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge badge-secondary"><?= htmlspecialchars(ucfirst($row['estado']), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-right"><?= htmlspecialchars(APP_CURRENCY_SYMBOL, ENT_QUOTES, 'UTF-8') ?> <?= number_format((float)$row['total'], 2, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($row['nombre_usuario'] ?? 'Usuario eliminado', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <a class="btn btn-info btn-sm"
                                                href="<?= BASE_URL ?>/documents/show/<?= (int)$row['id_documento'] ?>"
                                                aria-label="Ver documento <?= $prefix ?>-<?= (int)$row['id_documento'] ?>">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </a>
                                            <?php if ($canManage): ?>
                                                <a class="btn btn-success btn-sm"
                                                    href="<?= BASE_URL ?>/documents/edit/<?= (int)$row['id_documento'] ?>"
                                                    aria-label="Editar documento <?= $prefix ?>-<?= (int)$row['id_documento'] ?>">
                                                    <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($documents)): ?>
                                    <tr><td colspan="7" class="text-center text-muted">Aún no hay documentos registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mb-0">Estos documentos son registros comerciales internos; no son boletas, facturas ni DTE emitidos ante el SII.</p>
                </div>
            </div>
        </div>
    </section>
</section>

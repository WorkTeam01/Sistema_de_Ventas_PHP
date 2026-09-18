<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Devoluciones</h1>
                </div>
                <div class="col-sm-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Devoluciones</li>
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
                <div class="col-md-12">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h2 class="card-title"><i class="fas fa-list mr-1"></i> Historial de devoluciones</h2>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/returns/create" class="btn btn-warning btn-sm">
                                        <i class="fas fa-plus"></i> Nueva devolución
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                        aria-label="Colapsar tarjeta">
                                        <i class="fas fa-minus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($returns)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 text-success" aria-hidden="true"></i>
                                    <p class="mb-0">No hay devoluciones registradas.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="tbl-returns" class="table table-bordered table-hover table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th class="text-center">N° Devolución</th>
                                                <th class="text-center">N° Venta</th>
                                                <th class="text-center">Cliente</th>
                                                <th class="text-center">Motivo</th>
                                                <th class="text-center">Monto</th>
                                                <th class="text-center">Fecha</th>
                                                <th class="text-center">Usuario</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($returns as $ret): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <span class="badge badge-warning"><?= (int)$ret['nro_devolucion'] ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge badge-info"><?= (int)$ret['nro_venta'] ?></span>
                                                    </td>
                                                    <td><?= htmlspecialchars($ret['nombre_cliente'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars($ret['motivo'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-right">
                                                        <?= APP_CURRENCY_SYMBOL ?> <?= number_format((float)$ret['monto'], 2) ?>
                                                    </td>
                                                    <td class="text-center"><?= htmlspecialchars($ret['fyh_creacion'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars($ret['usuario_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-center">
                                                        <a href="<?= BASE_URL ?>/returns/show/<?= (int)$ret['id_devolucion'] ?>"
                                                            class="btn btn-info btn-sm"
                                                            data-toggle="tooltip" title="Ver detalle"
                                                            aria-label="Ver detalle de devolución <?= (int)$ret['nro_devolucion'] ?>">
                                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->
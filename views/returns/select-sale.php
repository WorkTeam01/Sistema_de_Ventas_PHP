<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Registrar devolución</h1>
                </div>
                <div class="col-sm-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/returns"><i class="fas fa-undo"></i> Devoluciones</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Seleccionar venta</li>
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
                                <h2 class="card-title">Seleccione la venta a devolver</h2>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/returns" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                        aria-label="Colapsar tarjeta">
                                        <i class="fas fa-minus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="tbl-sales" class="table table-sm table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center">N° Venta</th>
                                        <th>Cliente</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-center">Fecha</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($sales)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No hay ventas registradas.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($sales as $sale): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge badge-info"><?= htmlspecialchars($sale['nro_venta'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($sale['nombre_cliente'] ?? 'Consumidor final', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-right"><?= APP_CURRENCY_SYMBOL ?> <?= number_format((float)$sale['total_pagado'], 2) ?></td>
                                                <td class="text-center"><?= date('d/m/Y', strtotime($sale['fyh_creacion'])) ?></td>
                                                <td class="text-center">
                                                    <a href="<?= BASE_URL ?>/returns/create?sale_id=<?= (int)$sale['id_venta'] ?>"
                                                        class="btn btn-warning btn-sm" data-toggle="tooltip" title="Registrar devolución">
                                                        <i class="fas fa-undo"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->
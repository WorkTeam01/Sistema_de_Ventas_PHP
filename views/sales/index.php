<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Ventas</h1>
                </div>
                <div class="col-sm-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Ventas</li>
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
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title">Lista de ventas</h3>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/sales/create" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Nueva venta
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"
                                        aria-label="Colapsar tarjeta">
                                        <i class="fas fa-minus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="saleTable" class="table table-bordered table-hover table-striped table-sm"
                                style="visibility: hidden;">
                                <thead>
                                    <tr>
                                        <th class="text-center">Nro</th>
                                        <th class="text-center">N° Venta</th>
                                        <th class="text-center">Cliente</th>
                                        <th class="text-center">Total Pagado</th>
                                        <th class="text-center">Fecha</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador = 0;
                                    foreach ($sales_data as $sale) :
                                        $id_venta = (int)$sale['id_venta'];
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $contador += 1; ?></td>
                                            <td class="text-center"><?= htmlspecialchars($sale['nro_venta'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($sale['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-right">
                                                <?= APP_CURRENCY_SYMBOL ?> <?= htmlspecialchars(number_format((float)$sale['total_pagado'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($sale['fyh_creacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="<?= BASE_URL ?>/sales/show/<?= $id_venta ?>"
                                                        class="btn btn-info btn-sm" data-toggle="tooltip"
                                                        title="Ver detalles" aria-label="Ver detalles">
                                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/sales/invoice/<?= $id_venta ?>"
                                                        class="btn btn-success btn-sm" data-toggle="tooltip"
                                                        title="Ver factura" target="_blank" aria-label="Ver factura">
                                                        <i class="fas fa-print" aria-hidden="true"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/sales/delete/<?= $id_venta ?>"
                                                        class="btn btn-danger btn-sm" data-toggle="tooltip"
                                                        title="Eliminar venta" aria-label="Eliminar venta">
                                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->
<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Ventas</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Ventas</li>
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
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title">Lista de ventas</h3>
                                <div class="card-tools">
                                    <a href="<?= BASE_URL ?>/sales/create" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Nueva venta
                                    </a>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
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
                                            Bs. <?= htmlspecialchars(number_format((float)$sale['total_pagado'], 2), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center"><?= htmlspecialchars($sale['fyh_creacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="<?= BASE_URL ?>/sales/show/<?= $id_venta ?>"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="<?= BASE_URL ?>/sales/invoice/<?= $id_venta ?>"
                                                   class="btn btn-success btn-sm" target="_blank">
                                                    <i class="fas fa-print"></i> Factura
                                                </a>
                                                <a href="<?= BASE_URL ?>/sales/delete/<?= $id_venta ?>"
                                                   class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Eliminar
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

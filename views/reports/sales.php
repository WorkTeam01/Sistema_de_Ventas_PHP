<section class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1>Reporte de Ventas</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/reports"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                        <li class="breadcrumb-item active">Ventas</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Filtros -->
            <?php
            $action      = BASE_URL . '/reports/sales';
            $extraFields = [];
            include __DIR__ . '/partial/_date_filter.php';
            ?>

            <!-- Totalizadores -->
            <div class="row report-totals">
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-success elevation-1"><i class="fas fa-receipt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">N° de Ventas</span>
                            <span class="info-box-number"><?= (int)$totals['num_ventas'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-dollar-sign"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Ingresos</span>
                            <span class="info-box-number">Bs. <?= number_format((float)$totals['total_ingresos'], 2) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-info elevation-1"><i class="fas fa-chart-bar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Ticket Promedio</span>
                            <span class="info-box-number">Bs. <?= number_format((float)$totals['ticket_promedio'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <!-- Tabla -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    Ventas del <?= htmlspecialchars(date('d/m/Y', strtotime($filters['desde_display'])), ENT_QUOTES, 'UTF-8') ?>
                                    al <?= htmlspecialchars(date('d/m/Y', strtotime($filters['hasta_display'])), ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                <div class="card-tools">
                                    <?php
                                    $pdfParams = http_build_query([
                                        'fecha_desde' => $filters['desde_display'],
                                        'fecha_hasta' => $filters['hasta_display'],
                                        'export'      => 'pdf',
                                    ]);
                                    ?>
                                    <a href="<?= BASE_URL ?>/reports/sales?<?= $pdfParams ?>"
                                        class="btn btn-sm btn-danger" title="Exportar PDF" target="_blank">
                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="salesTable"
                                class="table table-bordered table-striped table-hover table-sm w-100"
                                style="visibility:hidden">
                                <thead>
                                    <tr>
                                        <th>N° Venta</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th class="text-right">Total (Bs.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= (int)$row['nro_venta'] ?></td>
                                            <td data-order="<?= htmlspecialchars($row['fyh_creacion'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= date('d/m/Y H:i', strtotime($row['fyh_creacion'])) ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['cliente'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-right"><?= number_format((float)$row['total_pagado'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</section>
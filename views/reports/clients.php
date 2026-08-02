<section class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1>Reporte de Clientes</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/reports"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                        <li class="breadcrumb-item active">Clientes</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Filtros -->
            <?php
            $action      = BASE_URL . '/reports/clients';
            $extraFields = [];
            include __DIR__ . '/partial/_date_filter.php';
            ?>

            <!-- Totalizadores -->
            <div class="row report-totals">
                <div class="col-md-6 col-lg-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">N° Clientes</span>
                            <span class="info-box-number"><?= count($rows) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Acumulado</span>
                            <span class="info-box-number">
                                <?= APP_CURRENCY_SYMBOL ?> <?= number_format($montoTotal, 2) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-users mr-1"></i>
                                    Clientes —
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($filters['desde_display'])), ENT_QUOTES, 'UTF-8') ?>
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
                                    <a href="<?= BASE_URL ?>/reports/clients?<?= $pdfParams ?>"
                                        class="btn btn-sm btn-danger" title="Exportar PDF" target="_blank">
                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="clientsTable"
                                class="table table-bordered table-striped table-hover table-sm w-100"
                                style="visibility:hidden">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>NIT/CI</th>
                                        <th>Email</th>
                                        <th class="text-right">N° Compras</th>
                                        <th class="text-right">Monto Acumulado (<?= APP_CURRENCY_SYMBOL ?>)</th>
                                        <th>Última Compra</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['nit_ci'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-right"><?= (int)$row['num_compras'] ?></td>
                                            <td class="text-right"><?= number_format((float)$row['monto_acumulado'], 2) ?></td>
                                            <td data-order="<?= htmlspecialchars($row['ultima_compra'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                <?= $row['ultima_compra'] ? date('d/m/Y H:i', strtotime($row['ultima_compra'])) : '—' ?>
                                            </td>
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
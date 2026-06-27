<section class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1>Reportes</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Reportes</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Navegación de reportes -->
            <div class="row">
                <?php if ($can['view_sales_report']): ?>
                    <div class="col-sm-6 col-lg-3">
                        <a href="<?= BASE_URL ?>/reports/sales" class="text-decoration-none">
                            <div class="info-box info-box-hover">
                                <span class="info-box-icon bg-success elevation-1">
                                    <i class="fas fa-chart-line"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Ventas por Período</span>
                                    <span class="info-box-number text-sm">Ver reporte &rsaquo;</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <a href="<?= BASE_URL ?>/reports/top-products" class="text-decoration-none">
                            <div class="info-box info-box-hover">
                                <span class="info-box-icon bg-warning elevation-1">
                                    <i class="fas fa-trophy"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Top Productos</span>
                                    <span class="info-box-number text-sm">Ver reporte &rsaquo;</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($can['view_purchases_report']): ?>
                    <div class="col-sm-6 col-lg-3">
                        <a href="<?= BASE_URL ?>/reports/purchases" class="text-decoration-none">
                            <div class="info-box info-box-hover">
                                <span class="info-box-icon bg-danger elevation-1">
                                    <i class="fas fa-shopping-cart"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Compras por Período</span>
                                    <span class="info-box-number text-sm">Ver reporte &rsaquo;</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 col-lg-3">
                        <a href="<?= BASE_URL ?>/reports/clients" class="text-decoration-none">
                            <div class="info-box info-box-hover">
                                <span class="info-box-icon bg-info elevation-1">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Clientes</span>
                                    <span class="info-box-number text-sm">Ver reporte &rsaquo;</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Resumen del mes actual -->
            <?php
            $mesLabel = date('F Y');
            $localeMonths = [
                'January' => 'Enero',
                'February' => 'Febrero',
                'March' => 'Marzo',
                'April'   => 'Abril',
                'May'       => 'Mayo',
                'June'  => 'Junio',
                'July'    => 'Julio',
                'August'    => 'Agosto',
                'September' => 'Septiembre',
                'October' => 'Octubre',
                'November' => 'Noviembre',
                'December' => 'Diciembre',
            ];
            $mesLabel = strtr($mesLabel, $localeMonths);
            ?>
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Resumen de <?= htmlspecialchars($mesLabel, ENT_QUOTES, 'UTF-8') ?>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">

                                <!-- Ventas del mes -->
                                <div class="col-sm-6 col-lg-3">
                                    <div class="info-box mb-3">
                                        <span class="info-box-icon bg-success elevation-1">
                                            <i class="fas fa-receipt"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">
                                                <?= !$can['view_sales_all'] ? 'Mis Ventas' : 'Ventas' ?>
                                            </span>
                                            <span class="info-box-number"><?= (int)$salesSummary['num_ventas'] ?></span>
                                            <span class="progress-description text-muted">
                                                Bs. <?= number_format((float)$salesSummary['total_ingresos'], 2) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($can['view_purchases_report']): ?>
                                    <!-- Compras del mes -->
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="info-box mb-3">
                                            <span class="info-box-icon bg-danger elevation-1">
                                                <i class="fas fa-shopping-cart"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Compras</span>
                                                <span class="info-box-number"><?= (int)$purchaseSummary['num_compras'] ?></span>
                                                <span class="progress-description text-muted">
                                                    Bs. <?= number_format((float)$purchaseSummary['total_egresos'], 2) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Clientes activos -->
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="info-box mb-3">
                                            <span class="info-box-icon bg-info elevation-1">
                                                <i class="fas fa-users"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Clientes con compras</span>
                                                <span class="info-box-number"><?= $clientesActivos ?></span>
                                                <span class="progress-description text-muted">en el mes</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Margen bruto -->
                                    <?php
                                    $margen = (float)$salesSummary['total_ingresos'] - (float)$purchaseSummary['total_egresos'];
                                    $margenClass = $margen >= 0 ? 'bg-primary' : 'bg-warning';
                                    ?>
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="info-box mb-3">
                                            <span class="info-box-icon <?= $margenClass ?> elevation-1">
                                                <i class="fas fa-balance-scale"></i>
                                            </span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Margen bruto</span>
                                                <span class="info-box-number">
                                                    <?= ($margen >= 0 ? '' : '-') ?>Bs. <?= number_format(abs($margen), 2) ?>
                                                </span>
                                                <span class="progress-description text-muted">ingresos − egresos</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <?php if ($can['view_purchases_report'] && !empty($topProductos)): ?>
                                <hr>
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-trophy mr-1 text-warning"></i> Top 5 productos más vendidos del mes
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="d-none d-sm-table-cell">#</th>
                                                <th>Producto</th>
                                                <th class="d-none d-md-table-cell">Categoría</th>
                                                <th class="text-right">Unidades</th>
                                                <th class="text-right">Ingresos (Bs.)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topProductos as $i => $p): ?>
                                                <tr>
                                                    <td class="d-none d-sm-table-cell">
                                                        <?php if ($i === 0): ?>
                                                            <i class="fas fa-medal text-warning"></i>
                                                        <?php elseif ($i === 1): ?>
                                                            <i class="fas fa-medal text-secondary"></i>
                                                        <?php elseif ($i === 2): ?>
                                                            <i class="fas fa-medal" style="color:#cd7f32"></i>
                                                        <?php else: ?>
                                                            <?= $i + 1 ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-muted d-none d-md-table-cell"><?= htmlspecialchars($p['categoria'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="text-right"><?= (int)$p['unidades_vendidas'] ?></td>
                                                    <td class="text-right"><?= number_format((float)$p['ingresos'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-right mt-2">
                                    <a href="<?= BASE_URL ?>/reports/top-products" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-trophy mr-1"></i> Ver reporte completo
                                    </a>
                                </div>
                            <?php elseif ($can['view_purchases_report']): ?>
                                <p class="text-muted text-center mb-0 mt-2">
                                    <i class="fas fa-info-circle mr-1"></i> Sin ventas registradas en este mes.
                                </p>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</section>
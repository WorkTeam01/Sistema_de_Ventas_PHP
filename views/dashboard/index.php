<section class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1 class="m-0">Dashboard &mdash; <?= htmlspecialchars($rol_sesion) ?></h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- ===== KPIs ===== -->
            <div class="row">

                <?php if ($can['view_sales']) : ?>
                    <!-- KPI: Ventas del mes -->
                    <div class="<?= $kpiCol ?> col-sm-6 col-12">
                        <div class="info-box elevation-1">
                            <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Ventas del mes</span>
                                <span class="info-box-number"><?= APP_CURRENCY_SYMBOL ?> <?= number_format($kpis['ventas_mes'], 2, ',', '.') ?></span>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?= $kpis['ventas_var_bar'] ?>%"></div>
                                </div>
                                <span class="progress-description <?= $kpis['ventas_var_cls'] ?>">
                                    <i class="fas <?= $kpis['ventas_var_ico'] ?>"></i>
                                    <?= ($kpis['ventas_var'] >= 0 ? '+' : '') . $kpis['ventas_var'] ?>% vs. mes anterior
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- KPI: Ventas hoy -->
                    <div class="<?= $kpiCol ?> col-sm-6 col-12">
                        <div class="info-box elevation-1">
                            <span class="info-box-icon bg-info"><i class="fas fa-cash-register"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Ventas hoy</span>
                                <span class="info-box-number">
                                    <?= $kpis['ventas_hoy']['cantidad'] ?>
                                    <small>ventas</small>
                                </span>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 100%"></div>
                                </div>
                                <span class="progress-description text-muted">
                                    <?= APP_CURRENCY_SYMBOL ?> <?= number_format($kpis['ventas_hoy']['monto'], 2, ',', '.') ?> recaudado
                                </span>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <?php if ($can['view_purchases']) : ?>
                    <!-- KPI: Compras del mes -->
                    <div class="<?= $kpiCol ?> col-sm-6 col-12">
                        <div class="info-box elevation-1">
                            <span class="info-box-icon bg-danger"><i class="fas fa-cart-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Compras del mes</span>
                                <span class="info-box-number"><?= APP_CURRENCY_SYMBOL ?> <?= number_format($kpis['compras_mes'], 2, ',', '.') ?></span>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" style="width: <?= $kpis['compras_var_bar'] ?>%"></div>
                                </div>
                                <span class="progress-description <?= $kpis['compras_var_cls'] ?>">
                                    <i class="fas <?= $kpis['compras_var_ico'] ?>"></i>
                                    <?= ($kpis['compras_var'] >= 0 ? '+' : '') . $kpis['compras_var'] ?>% vs. mes anterior
                                </span>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <!-- KPI: Stock bajo — todos los roles -->
                <div class="<?= $kpiCol ?> col-sm-6 col-12">
                    <div class="info-box elevation-1">
                        <span class="info-box-icon <?= $kpis['stock_critico'] ? 'bg-warning' : 'bg-success' ?>">
                            <i class="fas <?= $kpis['stock_critico'] ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Stock bajo</span>
                            <span class="info-box-number"><?= $kpis['low_stock_count'] ?></span>
                            <div class="progress">
                                <div class="progress-bar <?= $kpis['stock_critico'] ? 'bg-warning' : 'bg-success' ?>"
                                    style="width: 100%"></div>
                            </div>
                            <span class="progress-description text-muted">
                                <?= $kpis['stock_critico'] ? 'productos bajo el mínimo' : 'inventario en orden' ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /.row KPIs -->

            <!-- ===== KPIs Admin ===== -->
            <?php if (isset($kpis['utilidad_bruta']) || isset($kpis['clientes_nuevos'])) : ?>
                <a href="#admin-kpis" data-toggle="collapse" id="admin-kpis-toggle" class="text-secondary d-inline-flex align-items-center mb-2" style="font-size:0.78rem;" aria-expanded="true">
                    <i class="fas fa-coins mr-1"></i>
                    <span>Flujo del mes</span>
                    <i class="fas fa-arrow-up ml-1" id="admin-kpis-icon"></i>
                </a>
                <div class="row collapse show" id="admin-kpis">
                    <?php if (isset($kpis['utilidad_bruta'])) : ?>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="info-box elevation-1">
                                <span class="info-box-icon <?= $kpis['utilidad_positiva'] ? 'bg-primary' : 'bg-danger' ?>">
                                    <i class="fas fa-coins"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Ventas &minus; Compras del mes</span>
                                    <span class="info-box-number <?= $kpis['utilidad_positiva'] ? 'text-primary' : 'text-danger' ?>">
                                        <?= APP_CURRENCY_SYMBOL ?> <?= number_format($kpis['utilidad_bruta'], 2, ',', '.') ?>
                                    </span>
                                    <div class="progress">
                                        <div class="progress-bar <?= $kpis['utilidad_positiva'] ? 'bg-primary' : 'bg-danger' ?>" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description text-muted">flujo del mes actual</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($kpis['clientes_nuevos'])) : ?>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="info-box elevation-1">
                                <span class="info-box-icon bg-teal"><i class="fas fa-user-plus"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Clientes nuevos</span>
                                    <span class="info-box-number"><?= $kpis['clientes_nuevos'] ?></span>
                                    <div class="progress">
                                        <div class="progress-bar bg-teal" style="width: 100%"></div>
                                    </div>
                                    <span class="progress-description text-muted">registrados este mes</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- ===== Gráficos ===== -->
            <?php if (!empty($chartData['datasets'])) :
                $chartLabelsList = array_map(static fn ($d) => mb_strtolower($d['label']), $chartData['datasets']);
                $chartTitle      = implode(' vs ', array_map('ucfirst', $chartLabelsList));
                $chartDescriptor = implode(' y ', $chartLabelsList);
            ?>
                <div class="row align-items-stretch mb-3">
                    <div class="col-lg-8 col-12">
                        <div class="card card-outline card-success h-100">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    <?= htmlspecialchars($chartTitle) ?> — últimos 6 meses
                                </h2>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesPurchasesChart" role="img"
                                        data-currency="<?= htmlspecialchars(APP_CURRENCY_SYMBOL) ?>"
                                        aria-label="Gráfico de barras: <?= htmlspecialchars($chartDescriptor) ?> de los últimos 6 meses. Los valores exactos están disponibles en la tabla debajo del gráfico."></canvas>
                                </div>
                                <table class="sr-only">
                                    <caption>Datos del gráfico de <?= htmlspecialchars($chartDescriptor) ?> por mes</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Mes</th>
                                            <?php foreach ($chartData['datasets'] as $dataset) : ?>
                                                <th scope="col"><?= htmlspecialchars($dataset['label']) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($chartData['labels'] as $i => $label) : ?>
                                            <tr>
                                                <th scope="row"><?= htmlspecialchars($label) ?></th>
                                                <?php foreach ($chartData['datasets'] as $dataset) : ?>
                                                    <td><?= APP_CURRENCY_SYMBOL ?> <?= number_format($dataset['data'][$i], 2, ',', '.') ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($topChart['quantities'])) : ?>
                        <div class="col-lg-4 col-12">
                            <div class="card card-outline card-success h-100">
                                <div class="card-header">
                                    <h2 class="card-title">
                                        <i class="fas fa-trophy mr-1"></i>
                                        Top 5 productos &mdash; histórico
                                    </h2>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container-sm">
                                        <canvas id="topProductsChart" role="img"
                                            data-currency="<?= htmlspecialchars(APP_CURRENCY_SYMBOL) ?>"
                                            aria-label="Gráfico de dona: top 5 productos más vendidos. Los valores exactos están disponibles en la tabla debajo del gráfico."></canvas>
                                    </div>
                                    <table class="sr-only">
                                        <caption>Datos del top 5 productos más vendidos</caption>
                                        <thead>
                                            <tr>
                                                <th scope="col">Producto</th>
                                                <th scope="col">Unidades</th>
                                                <th scope="col">Ingresos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topChart['labels'] as $i => $label) : ?>
                                                <tr>
                                                    <th scope="row"><?= htmlspecialchars($label) ?></th>
                                                    <td><?= $topChart['quantities'][$i] ?></td>
                                                    <td><?= APP_CURRENCY_SYMBOL ?> <?= number_format($topChart['revenues'][$i], 2, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <script type="application/json" id="dashboard-top-data">
                                <?= json_encode($topChart, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
                            </script>
                        </div>
                    <?php endif; ?>
                </div>

                <script type="application/json" id="dashboard-chart-data">
                    <?= json_encode($chartData, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
                </script>
            <?php endif; ?>

            <!-- ===== Últimas ventas ===== -->
            <?php if (!empty($kpis['ultimas_ventas'])) : ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-history mr-1"></i>
                                    Últimas ventas
                                </h2>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Cliente</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($kpis['ultimas_ventas'] as $venta) : ?>
                                            <tr>
                                                <td>
                                                    <a href="<?= BASE_URL ?>/sales/show/<?= $venta['id_venta'] ?>">
                                                        #<?= $venta['nro_venta'] ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($venta['nombre_cliente']) ?></td>
                                                <td class="text-right text-success font-weight-bold">
                                                    <?= APP_CURRENCY_SYMBOL ?> <?= number_format($venta['total_pagado'], 2, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-center p-2">
                                <a href="<?= BASE_URL ?>/sales" class="text-info">
                                    Ver todas <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /.container-fluid -->
    </div><!-- /.content -->
</section><!-- /.content-wrapper -->
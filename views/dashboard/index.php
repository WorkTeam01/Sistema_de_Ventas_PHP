<?php
// --- Preparar datos para el gráfico ---
$chartLabels   = [];
$chartVentas   = [];
$chartCompras  = [];
$chartDatasets = [];

$mesesBase = [];
for ($i = 5; $i >= 0; $i--) {
    $mesesBase[date('Y-m', strtotime("-$i months"))] = 0;
}

if (!empty($kpis['ventas_por_mes'])) {
    $ventasPorMes = $mesesBase;
    foreach ($kpis['ventas_por_mes'] as $row) {
        $ventasPorMes[$row['mes']] = (float) $row['total'];
    }
    $chartVentas = array_values($ventasPorMes);
}

if (!empty($kpis['compras_por_mes'])) {
    $comprasPorMes = $mesesBase;
    foreach ($kpis['compras_por_mes'] as $row) {
        $comprasPorMes[$row['mes']] = (float) $row['total'];
    }
    $chartCompras = array_values($comprasPorMes);
}

$mesesEs = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
foreach (array_keys($mesesBase) as $mesKey) {
    [$anio, $mes] = explode('-', $mesKey);
    $chartLabels[] = $mesesEs[(int)$mes - 1] . ' ' . $anio;
}

if (!empty($chartVentas)) {
    $chartDatasets[] = [
        'label'           => 'Ventas',
        'data'            => $chartVentas,
        'backgroundColor' => 'rgba(40,167,69,0.75)',
        'borderColor'     => 'rgba(40,167,69,1)',
        'borderWidth'     => 1,
    ];
}
if (!empty($chartCompras)) {
    $chartDatasets[] = [
        'label'           => 'Compras',
        'data'            => $chartCompras,
        'backgroundColor' => 'rgba(220,53,69,0.75)',
        'borderColor'     => 'rgba(220,53,69,1)',
        'borderWidth'     => 1,
    ];
}

// Columna según cuántos KPIs ve el rol
$kpiCount = 1; // stock bajo siempre
if ($rol_sesion === 'Administrador' || $rol_sesion === 'Vendedor')  $kpiCount += 2;
if ($rol_sesion === 'Administrador' || $rol_sesion === 'Comprador') $kpiCount += 1;
$kpiCol = $kpiCount >= 4 ? 'col-lg-3 col-md-6' : ($kpiCount === 3 ? 'col-lg-4 col-md-6' : 'col-lg-6 col-md-6');
?>

<div class="content-wrapper">
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

                <?php if ($rol_sesion === 'Administrador' || $rol_sesion === 'Vendedor') : ?>
                <?php
                $var    = $kpis['ventas_var'];
                $varCls = $var > 0 ? 'text-success' : ($var < 0 ? 'text-danger' : 'text-muted');
                $varIco = $var > 0 ? 'fa-arrow-up' : ($var < 0 ? 'fa-arrow-down' : 'fa-minus');
                $varBar = min(abs($var), 100);
                ?>

                <!-- KPI: Ventas del mes -->
                <div class="<?= $kpiCol ?> col-sm-6 col-12">
                    <div class="info-box elevation-1">
                        <span class="info-box-icon bg-success"><i class="fas fa-dollar-sign"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Ventas del mes</span>
                            <span class="info-box-number">Bs <?= number_format($kpis['ventas_mes'], 2, ',', '.') ?></span>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: <?= $varBar ?>%"></div>
                            </div>
                            <span class="progress-description <?= $varCls ?>">
                                <i class="fas <?= $varIco ?>"></i>
                                <?= ($var >= 0 ? '+' : '') . $var ?>% vs. mes anterior
                                &mdash; <a href="<?= BASE_URL ?>/sales" class="text-muted">ver ventas</a>
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
                                Bs <?= number_format($kpis['ventas_hoy']['monto'], 2, ',', '.') ?> recaudado hoy
                                &mdash; <a href="<?= BASE_URL ?>/sales/create" class="text-muted">nueva venta</a>
                            </span>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

                <?php if ($rol_sesion === 'Administrador' || $rol_sesion === 'Comprador') : ?>
                <?php
                $var    = $kpis['compras_var'];
                // Para compras: subida es negativa (más gasto), bajada es positiva
                $varCls = $var > 0 ? 'text-danger' : ($var < 0 ? 'text-success' : 'text-muted');
                $varIco = $var > 0 ? 'fa-arrow-up' : ($var < 0 ? 'fa-arrow-down' : 'fa-minus');
                $varBar = min(abs($var), 100);
                ?>

                <!-- KPI: Compras del mes -->
                <div class="<?= $kpiCol ?> col-sm-6 col-12">
                    <div class="info-box elevation-1">
                        <span class="info-box-icon bg-danger"><i class="fas fa-cart-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Compras del mes</span>
                            <span class="info-box-number">Bs <?= number_format($kpis['compras_mes'], 2, ',', '.') ?></span>
                            <div class="progress">
                                <div class="progress-bar bg-danger" style="width: <?= $varBar ?>%"></div>
                            </div>
                            <span class="progress-description <?= $varCls ?>">
                                <i class="fas <?= $varIco ?>"></i>
                                <?= ($var >= 0 ? '+' : '') . $var ?>% vs. mes anterior
                                &mdash; <a href="<?= BASE_URL ?>/purchases" class="text-muted">ver compras</a>
                            </span>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

                <!-- KPI: Stock bajo — todos los roles -->
                <?php $stockCritico = $kpis['low_stock_count'] > 0; ?>
                <div class="<?= $kpiCol ?> col-sm-6 col-12">
                    <div class="info-box elevation-1">
                        <span class="info-box-icon <?= $stockCritico ? 'bg-warning' : 'bg-success' ?>">
                            <i class="fas <?= $stockCritico ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Stock bajo</span>
                            <span class="info-box-number"><?= $kpis['low_stock_count'] ?></span>
                            <div class="progress">
                                <div class="progress-bar <?= $stockCritico ? 'bg-warning' : 'bg-success' ?>" style="width: 100%"></div>
                            </div>
                            <span class="progress-description text-muted">
                                <?= $stockCritico ? 'productos bajo el mínimo' : 'inventario en orden' ?>
                                &mdash; <a href="<?= BASE_URL ?>/products" class="text-muted">ver inventario</a>
                            </span>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /.row KPIs -->

            <!-- ===== Gráfico + Últimas ventas ===== -->
            <?php if (!empty($chartDatasets)) : ?>
            <div class="row">
                <div class="col-lg-8 col-12">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-bar mr-1"></i>
                                Ventas<?= !empty($chartCompras) ? ' vs Compras' : '' ?> — últimos 6 meses
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="salesPurchasesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($kpis['ultimas_ventas'])) : ?>
                <div class="col-lg-4 col-12">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-1"></i>
                                Últimas ventas
                            </h3>
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
                                            Bs <?= number_format($venta['total_pagado'], 2, ',', '.') ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-right p-2">
                            <a href="<?= BASE_URL ?>/sales" class="text-sm">
                                Ver todas <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <script type="application/json" id="dashboard-chart-data">
            <?= json_encode(['labels' => $chartLabels, 'datasets' => $chartDatasets], JSON_THROW_ON_ERROR) ?>
            </script>
            <?php endif; ?>

            <!-- ===== Productos con stock bajo ===== -->
            <?php if (!empty($kpis['low_stock_products'])) : ?>
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exclamation-triangle mr-1 text-warning"></i>
                                Productos con stock bajo
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:45px"></th>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th class="text-center">Stock actual</th>
                                        <th class="text-center">Mínimo</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kpis['low_stock_products'] as $prod) : ?>
                                    <?php $critico = (int)$prod['stock'] === 0; ?>
                                    <tr>
                                        <td>
                                            <?php if ($prod['imagen']) : ?>
                                                <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($prod['imagen']) ?>"
                                                     class="dashboard-product-img" alt="">
                                            <?php else : ?>
                                                <span class="dashboard-product-img d-inline-flex align-items-center justify-content-center bg-light text-muted">
                                                    <i class="fas fa-image"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($prod['codigo']) ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/products/show/<?= $prod['id_producto'] ?>">
                                                <?= htmlspecialchars($prod['nombre']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($prod['nombre_categoria']) ?></td>
                                        <td class="text-center font-weight-bold <?= $critico ? 'text-danger' : 'text-warning' ?>">
                                            <?= $prod['stock'] ?>
                                        </td>
                                        <td class="text-center"><?= $prod['stock_minimo'] ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $critico ? 'badge-stock-critical' : 'badge-stock-warning' ?>">
                                                <?= $critico ? 'Sin stock' : 'Stock bajo' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-right p-2">
                            <a href="<?= BASE_URL ?>/products" class="text-sm">
                                Ver inventario completo <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.container-fluid -->
    </div><!-- /.content -->
</div><!-- /.content-wrapper -->
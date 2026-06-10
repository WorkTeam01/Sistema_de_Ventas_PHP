<section class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1>Top Productos más Vendidos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/reports"><i class="fas fa-chart-bar"></i> Reportes</a></li>
                        <li class="breadcrumb-item active">Top Productos</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">

            <!-- Filtros -->
            <?php
            $action       = BASE_URL . '/reports/top-products';
            $dateColClass = 'col-lg-2';

            ob_start(); ?>
            <div class="col-sm-6 col-lg-2">
                <div class="form-group mb-0">
                    <label for="categoria">Categoría</label>
                    <select name="categoria" id="categoria" class="form-control select2">
                        <option value="">Todas</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id_categoria'] ?>"
                                <?= (int)$cat['id_categoria'] === $categoria ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre_categoria'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="form-group mb-0">
                    <label for="top">Mostrar</label>
                    <select name="top" id="top" class="form-control select2">
                        <?php foreach ([5, 10, 20, 50] as $n): ?>
                            <option value="<?= $n ?>" <?= $top === $n ? 'selected' : '' ?>>
                                Top <?= $n ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-sm-6 col-lg-2">
                <div class="form-group mb-0">
                    <label for="orden">Ordenar por</label>
                    <select name="orden" id="orden" class="form-control select2">
                        <option value="cantidad" <?= $orden === 'cantidad' ? 'selected' : '' ?>>Cantidad</option>
                        <option value="ingresos" <?= $orden === 'ingresos' ? 'selected' : '' ?>>Ingresos</option>
                    </select>
                </div>
            </div>
            <?php $extraFields = [ob_get_clean()];
            include __DIR__ . '/partial/_date_filter.php';
            ?>

            <div class="row">
                <div class="col-12">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-trophy mr-1"></i>
                                    Top <?= (int)$top ?> productos —
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($filters['desde_display'])), ENT_QUOTES, 'UTF-8') ?>
                                    al <?= htmlspecialchars(date('d/m/Y', strtotime($filters['hasta_display'])), ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                <div class="card-tools">
                                    <?php
                                    $pdfParams = http_build_query([
                                        'fecha_desde' => $filters['desde_display'],
                                        'fecha_hasta' => $filters['hasta_display'],
                                        'top'         => $top,
                                        'orden'       => $orden,
                                        'categoria'   => $categoria ?: '',
                                        'export'      => 'pdf',
                                    ]);
                                    ?>
                                    <a href="<?= BASE_URL ?>/reports/top-products?<?= $pdfParams ?>"
                                        class="btn btn-sm btn-danger" title="Exportar PDF" target="_blank">
                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="topProductsTable"
                                class="table table-bordered table-striped table-hover table-sm w-100"
                                style="visibility:hidden"
                                data-orden="<?= htmlspecialchars($orden, ENT_QUOTES, 'UTF-8') ?>">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th class="text-right">Unidades Vendidas</th>
                                        <th class="text-right">Ingresos (Bs.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $i => $row): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['categoria'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-right"><?= (int)$row['unidades_vendidas'] ?></td>
                                            <td class="text-right"><?= number_format((float)$row['ingresos'], 2) ?></td>
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
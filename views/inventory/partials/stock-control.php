<?php
$alertas = array_filter($productos, fn($p) => (int)$p['stock'] <= (int)$p['stock_minimo']);
?>

<!-- Alertas de Stock Bajo -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Alertas de Stock Bajo</h3>
                <div class="card-tools m-0">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Stock Actual</th>
                                <th class="text-center d-none d-sm-table-cell">Mínimo</th>
                                <th class="text-center d-none d-md-table-cell">Estado</th>
                                <th class="text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($alertas)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-check-circle mr-2 text-success"></i> No hay alertas de stock
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($alertas as $p): ?>
                                    <tr>
                                        <td class="align-middle">
                                            <strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($p['codigo'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold text-danger">
                                            <?= (int)$p['stock'] ?>
                                        </td>
                                        <td class="text-center align-middle text-muted d-none d-sm-table-cell">
                                            <?= (int)$p['stock_minimo'] ?>
                                        </td>
                                        <td class="text-center align-middle d-none d-md-table-cell">
                                            <?php if ((int)$p['stock'] <= 0): ?>
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-exclamation-circle mr-1"></i> Agotado
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">
                                                    Quedan <?= (int)$p['stock'] ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button class="btn btn-xs btn-warning font-weight-bold btn-compra-rapida"
                                                    data-id="<?= (int)$p['id_producto'] ?>"
                                                    data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-shopping-cart mr-1"></i> Comprar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer p-2 bg-white"></div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Estado actual del inventario con barras de progreso -->
    <div class="col-12 col-lg-5">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-warehouse mr-1"></i> Estado del Inventario</h3>
                <div class="card-tools m-0">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th>Producto</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center d-none d-sm-table-cell">Nivel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle mr-2"></i> No hay productos registrados
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productos as $p):
                                    $stockVal = (int)$p['stock'];
                                    $minVal   = (int)$p['stock_minimo'];
                                    $maxVal   = isset($p['stock_maximo']) && $p['stock_maximo'] !== null
                                        ? (int)$p['stock_maximo']
                                        : ($minVal * 2 ?: 100);
                                    $pct      = $maxVal > 0 ? min(round(($stockVal / $maxVal) * 100), 100) : 0;
                                    if ($stockVal <= $minVal) {
                                        $barClass = 'bg-danger';
                                    } elseif ($stockVal <= (int)($minVal * 1.5)) {
                                        $barClass = 'bg-warning';
                                    } else {
                                        $barClass = 'bg-success';
                                    }
                                    $stockText = (isset($p['stock_maximo']) && $p['stock_maximo'] !== null)
                                        ? $stockVal . '/' . (int)$p['stock_maximo']
                                        : $stockVal;
                                ?>
                                    <tr>
                                        <td class="py-2">
                                            <strong><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($p['codigo'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold <?= $stockVal <= $minVal ? 'text-danger' : '' ?>">
                                            <?= $stockText ?>
                                            <?php if ($stockVal <= 0): ?>
                                                <span class="badge badge-danger d-block mt-1">Agotado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="align-middle d-none d-sm-table-cell" style="width: 40%;">
                                            <div class="progress progress-xs mb-0" style="min-width: 60px;"
                                                data-toggle="tooltip"
                                                title="<?= $pct ?>% del cupo ideal">
                                                <div class="progress-bar <?= $barClass ?>" style="width: <?= $pct ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer p-2 bg-white"></div>
        </div>
    </div>

    <!-- Últimos ajustes de stock -->
    <div class="col-12 col-lg-7">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-history mr-1"></i> Últimos Ajustes</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light border-bottom">
                            <tr>
                                <th class="d-none d-sm-table-cell">Fecha</th>
                                <th>Producto</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Cant.</th>
                                <th class="d-none d-md-table-cell">Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimosAjustes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle mr-2"></i> No hay ajustes registrados aún
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ultimosAjustes as $aj): ?>
                                    <tr>
                                        <td class="text-center d-none d-sm-table-cell">
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($aj['fyh_creacion'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($aj['producto_nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($aj['tipo'] === 'entrada'): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-arrow-up mr-1"></i> Entrada
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-arrow-down mr-1"></i> Salida
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center font-weight-bold <?= $aj['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                                            <?= $aj['tipo'] === 'entrada' ? '+' : '-' ?><?= (int)$aj['cantidad'] ?>
                                        </td>
                                        <td class="d-none d-md-table-cell text-muted">
                                            <small><?= htmlspecialchars($aj['motivo'], ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer p-2 text-center">
                <a href="<?= BASE_URL ?>/inventory?tab=ajustes" class="text-sm text-secondary">
                    Ver historial completo <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>
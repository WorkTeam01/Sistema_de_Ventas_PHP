<?php
/**
 * Partial compartido de filtros de fecha para todos los reportes.
 *
 * Variables esperadas:
 *   $action      — URL del formulario (BASE_URL . '/reports/sales', etc.)
 *   $filters     — array con claves 'desde_display' y 'hasta_display'
 *   $extraFields — (opcional) array de strings HTML con inputs adicionales
 */
$extraFields   = $extraFields   ?? [];
$dateColClass  = $dateColClass  ?? 'col-lg-3';
?>
<div class="card card-outline card-secondary collapsed-card mb-3">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h2>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="form-filters" action="<?= $action ?>" method="GET">
            <div class="row align-items-end">
                <div class="col-sm-6 <?= $dateColClass ?>">
                    <div class="form-group mb-0">
                        <label for="fecha_desde">Desde</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button type="button" class="input-group-text"
                                        data-toggle="date-picker"
                                        aria-label="Abrir selector de fecha"
                                        data-target="fecha_desde">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                            </div>
                            <input type="date" name="fecha_desde" id="fecha_desde" class="form-control"
                                   value="<?= htmlspecialchars($filters['desde_display'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 <?= $dateColClass ?>">
                    <div class="form-group mb-0">
                        <label for="fecha_hasta">Hasta</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button type="button" class="input-group-text"
                                        data-toggle="date-picker"
                                        aria-label="Abrir selector de fecha"
                                        data-target="fecha_hasta">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                            </div>
                            <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control"
                                   value="<?= htmlspecialchars($filters['hasta_display'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
                <?php foreach ($extraFields as $field): ?>
                    <?= $field ?>
                <?php endforeach; ?>
                <div class="col-sm-6 col-lg-2 mt-2 mt-lg-0">
                    <div class="form-group mb-0">
                        <label class="d-block">&nbsp;</label>
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-1"></i> Filtrar
                            </button>
                            <a href="<?= $action ?>" class="btn btn-default" id="btn-clear-filters">
                                <i class="fas fa-eraser"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

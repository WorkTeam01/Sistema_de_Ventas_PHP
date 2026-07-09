<!-- Filtros -->
<div class="card card-outline card-secondary collapsed-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="<?= BASE_URL ?>/activity-log">
            <div class="row">
                <!-- Desde -->
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>Desde <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend" style="cursor: pointer;"
                                onclick="document.getElementById('desde').showPicker()">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="date" id="desde" name="desde"
                                class="form-control"
                                value="<?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?>"
                                max="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <!-- Hasta -->
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label>Hasta <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend" style="cursor: pointer;"
                                onclick="document.getElementById('hasta').showPicker()">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="date" id="hasta" name="hasta"
                                class="form-control"
                                value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>"
                                max="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <!-- Entidad -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label>Entidad</label>
                        <select name="entity" class="form-control select2">
                            <option value="">— Todas —</option>
                            <?php foreach ($entities as $e): ?>
                                <option value="<?= htmlspecialchars($e['entidad'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ($filters['entity'] === $e['entidad']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($e['entidad']), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Acción -->
                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label>Acción</label>
                        <select name="action" class="form-control select2">
                            <option value="">— Todas —</option>
                            <?php foreach ($actions as $a): ?>
                                <option value="<?= htmlspecialchars($a['accion'], ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ($filters['action'] === $a['accion']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['accion'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Botones -->
                <div class="col-md-2 col-sm-12 d-flex align-items-end">
                    <div class="form-group w-100">
                        <div class="btn-group btn-block">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="<?= BASE_URL ?>/activity-log"
                                class="btn btn-secondary"
                                title="Limpiar filtros">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                El rango máximo es de 90 días. Mostrando del
                <strong><?= htmlspecialchars($from, ENT_QUOTES, 'UTF-8') ?></strong>
                al
                <strong><?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?></strong>.
            </small>
        </form>
    </div>
</div>

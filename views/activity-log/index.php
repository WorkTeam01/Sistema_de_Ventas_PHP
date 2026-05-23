<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Auditoría</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item active">Auditoría</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">

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

            <!-- Tabla -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-1"></i>
                        Registros de auditoría
                        <span class="badge badge-secondary ml-2"><?= count($logs) ?></span>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="activityLogTable"
                        class="table table-bordered table-hover table-striped table-sm"
                        style="visibility: hidden;">
                        <thead>
                            <tr>
                                <th class="text-center">Fecha/Hora</th>
                                <th>Usuario</th>
                                <th class="text-center">Acción</th>
                                <th class="text-center">Entidad</th>
                                <th class="text-center">ID</th>
                                <th>Descripción</th>
                                <th class="text-center">Ver</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-center text-nowrap">
                                        <?= htmlspecialchars($log['fyh_creacion'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($log['nombre_display'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($log['id_usuario'] === null): ?>
                                            <span class="badge badge-secondary badge-sm ml-1">eliminado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $badgeClass = match ($log['accion']) {
                                            'delete'       => 'badge-danger',
                                            'price_change' => 'badge-warning',
                                            'role_change'  => 'badge-info',
                                            default        => 'badge-primary',
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($log['accion'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?= htmlspecialchars(ucfirst($log['entidad']), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $log['entidad_id'] !== null ? (int)$log['entidad_id'] : '—' ?>
                                    </td>
                                    <td><?= htmlspecialchars($log['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/activity-log/show/<?= (int)$log['id_log'] ?>"
                                            class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>
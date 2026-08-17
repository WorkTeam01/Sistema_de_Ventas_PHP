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

            <div class="row">
                <div class="col-12">
                    <?php include __DIR__ . '/partial/_filters.php'; ?>

                    <?php include __DIR__ . '/partial/_kpis.php'; ?>

                    <!-- Tabla -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-1"></i>
                                Registros de auditoría
                                <span class="badge badge-secondary ml-2"><?= count($logs) ?></span>
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
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
                                                <span class="badge <?= \App\Helpers\ActivityLogRenderer::badgeClass($log['accion']) ?> px-2 py-1">
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
                                                <div class="btn-group">
                                                    <a href="<?= BASE_URL ?>/activity-log/show/<?= (int)$log['id_log'] ?>"
                                                        class="btn btn-info btn-sm" data-toggle="tooltip" title="Ver detalle"
                                                        aria-label="Ver detalle del registro #<?= (int)$log['id_log'] ?>">
                                                        <i class="fas fa-eye"></i>
                                                        <span class="sr-only">Ver detalle</span>
                                                    </a>
                                                </div>
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
    </div>
</section>
<?php
$accion  = $entry['accion']  ?? '';
$entidad = $entry['entidad'] ?? '';

$badgeClass = match ($accion) {
    'delete'       => 'badge-danger',
    'price_change' => 'badge-warning',
    'role_change'  => 'badge-info',
    default        => 'badge-primary',
};
?>
<!-- Content Wrapper -->
<section class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Detalle de auditoría</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/activity-log"><i class="fas fa-history"></i> Auditoría</a></li>
                        <li class="breadcrumb-item active">Detalle #<?= (int)$entry['id_log'] ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">

                <!-- Información del evento -->
                <div class="col-md-5">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Información del evento</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th class="w-35 bg-light">ID Log</th>
                                    <td><?= (int)$entry['id_log'] ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Fecha/Hora</th>
                                    <td><?= htmlspecialchars($entry['fyh_creacion'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Usuario</th>
                                    <td>
                                        <?= htmlspecialchars($entry['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($entry['id_usuario'] === null): ?>
                                            <span class="badge badge-secondary ml-1">eliminado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Acción</th>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($accion, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Entidad</th>
                                    <td><?= htmlspecialchars(ucfirst($entidad), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">ID Entidad</th>
                                    <td><?= $entry['entidad_id'] !== null ? (int)$entry['entidad_id'] : '—' ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">IP</th>
                                    <td><?= htmlspecialchars($entry['ip_address'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Descripción</th>
                                    <td><?= htmlspecialchars($entry['descripcion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-12 col-sm-auto">
                                    <a href="<?= BASE_URL ?>/activity-log" class="btn btn-default w-100">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <!-- Estado anterior -->
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-undo mr-1"></i> Estado anterior</h3>
                        </div>
                        <div class="card-body p-0">
                            <?php
                            $rows    = $rowsBefore;
                            $panelId = 'before';
                            include __DIR__ . '/partial/_data-panel.php';
                            ?>
                        </div>
                    </div>

                    <!-- Estado nuevo -->
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-check-circle mr-1"></i> Estado nuevo</h3>
                        </div>
                        <div class="card-body p-0">
                            <?php
                            $rows    = $rowsAfter;
                            $panelId = 'after';
                            include __DIR__ . '/partial/_data-panel.php';
                            ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
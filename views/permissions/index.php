<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Lista de permisos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item active">Permisos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center">
                                <h2 class="card-title">Permisos registrados</h2>
                                <div class="card-tools d-flex">
                                    <button type="button" class="btn btn-primary btn-sm me-2" data-toggle="modal"
                                            data-target="#modalCreate">
                                        <i class="fas fa-plus"></i> Nuevo permiso
                                    </button>
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" aria-label="Colapsar sección">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="permissionTable" class="table table-bordered table-hover table-striped table-sm"
                                   style="visibility: hidden;">
                                <thead>
                                <tr>
                                    <th class="text-center">Nro</th>
                                    <th class="text-center">Clave</th>
                                    <th class="text-center">Descripción</th>
                                    <th class="text-center">Módulo</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $contador = 0; ?>
                                <?php foreach ($permisos_datos as $permiso): ?>
                                    <tr>
                                        <td class="text-center"><?= ++$contador ?></td>
                                        <td><?= htmlspecialchars($permiso['clave'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($permiso['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($permiso['modulo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-success btn-sm btn-edit"
                                                        data-id="<?= $permiso['id_permiso'] ?>"
                                                        data-toggle="tooltip"
                                                        title="Editar"
                                                        aria-label="Editar permiso <?= htmlspecialchars($permiso['clave'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                    <span class="sr-only">Editar</span>
                                                </button>
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
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->

<?php require __DIR__ . '/partial/_modals.php'; ?>

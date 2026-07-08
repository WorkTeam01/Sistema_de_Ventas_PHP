<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Permisos del rol: <?= htmlspecialchars($role['rol'], ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/roles"><i class="fas fa-user-tag"></i> Roles</a></li>
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
                        <div class="card-header d-flex flex-wrap align-items-center">
                            <h3 class="card-title mb-2 mb-md-0">
                                Asignar permisos
                                <span class="badge badge-primary ml-1" id="contadorPermisos"></span>
                            </h3>
                            <div class="card-tools d-flex flex-wrap align-items-center ml-md-auto mb-2 mb-md-0">
                                <button type="button" class="btn btn-outline-primary btn-sm mr-1 mb-1" id="btnSeleccionarTodos">
                                    <i class="fas fa-check-square"></i> <span class="d-none d-sm-inline">Seleccionar todos</span><span class="d-inline d-sm-none">Todos</span>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm mr-1 mr-sm-2 mb-1" id="btnDeseleccionarTodos">
                                    <i class="far fa-square"></i> <span class="d-none d-sm-inline">Deseleccionar todos</span><span class="d-inline d-sm-none">Ninguno</span>
                                </button>
                                <button type="button" class="btn btn-tool mb-1" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <form id="formPermisos">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="card-body">
                                <?php if (empty($permisos_agrupados)): ?>
                                    <p class="text-muted mb-0">No hay permisos registrados en el catálogo.</p>
                                <?php endif; ?>
                                <?php foreach ($permisos_agrupados as $modulo => $permisos): ?>
                                    <div class="mb-4 modulo-permisos">
                                        <h5 class="border-bottom pb-1 d-flex flex-wrap justify-content-between align-items-center">
                                            <span><?= htmlspecialchars(ucfirst($modulo), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="form-check mb-0">
                                                <input type="checkbox" class="form-check-input modulo-toggle"
                                                    id="modulo_<?= htmlspecialchars($modulo, ENT_QUOTES, 'UTF-8') ?>">
                                                <label class="form-check-label small text-muted" for="modulo_<?= htmlspecialchars($modulo, ENT_QUOTES, 'UTF-8') ?>">
                                                    Seleccionar módulo
                                                </label>
                                            </span>
                                        </h5>
                                        <div class="row">
                                            <?php foreach ($permisos as $permiso): ?>
                                                <div class="col-12 col-sm-6 col-md-4">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input permiso-checkbox"
                                                            id="permiso_<?= $permiso['id_permiso'] ?>"
                                                            name="permisos[]"
                                                            value="<?= $permiso['id_permiso'] ?>"
                                                            <?= in_array((int)$permiso['id_permiso'], $permisos_asignados, true) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="permiso_<?= $permiso['id_permiso'] ?>">
                                                            <?= htmlspecialchars($permiso['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                                                            <small class="text-muted d-block"><?= htmlspecialchars($permiso['clave'], ENT_QUOTES, 'UTF-8') ?></small>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL ?>/roles" class="btn btn-default w-100">
                                            <i class="fas fa-arrow-left"></i> Volver
                                        </a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" class="btn btn-primary w-100" id="btnGuardarPermisos"
                                            data-id="<?= $role['id_rol'] ?>">
                                            <i class="fas fa-save"></i> Guardar permisos
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</section>
<!-- /.content-wrapper -->
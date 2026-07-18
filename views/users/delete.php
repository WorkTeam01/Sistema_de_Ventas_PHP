<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Eliminar usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/users"><i class="fas fa-users"></i>
                                Usuarios</a></li>
                        <li class="breadcrumb-item active">Eliminar usuario</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <form id="formEliminar" action="<?= BASE_URL; ?>/users/delete" method="post">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="id_usuario" value="<?= $id_usuario; ?>">

                        <div class="card card-danger card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Confirmación de
                                    eliminación</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                            class="fas fa-minus"></i></button>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="alert alert-danger">
                                    <h5><i class="icon fas fa-ban"></i> Acción irreversible</h5>
                                    Esta operación eliminará permanentemente al usuario seleccionado.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nombres">Nombres</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                </div>
                                                <input type="text" id="nombres"
                                                    value="<?= htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="form-control" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i
                                                            class="fas fa-envelope"></i></span>
                                                </div>
                                                <input type="email" id="email"
                                                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="form-control" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="rol">Rol</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i
                                                            class="fas fa-user-tag"></i></span>
                                                </div>
                                                <input type="text" id="rol"
                                                    value="<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?>"
                                                    class="form-control" disabled>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-0">
                                            <label for="id_usuario">ID</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                </div>
                                                <input type="text" id="id_usuario" value="<?= $id_usuario; ?>"
                                                    class="form-control" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL; ?>/users" class="btn btn-default w-100"><i
                                                class="fas fa-times"></i> Cancelar</a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="button" class="btn btn-danger w-100 btn-confirm-delete-user"
                                            data-nombre="<?= htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-trash"></i> Eliminar usuario
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-4">
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shield-alt"></i> Verificación</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                        class="fas fa-minus"></i></button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-sm text-muted mb-2">Confirma que estás eliminando el usuario correcto.</p>
                            <p class="text-sm text-muted mb-2">Si el usuario tiene actividad histórica, evalúa
                                desactivarlo en lugar de eliminarlo.</p>
                            <p class="text-sm text-muted mb-0">Esta acción no se puede deshacer.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </div>
</section>
<!-- /.content-wrapper -->
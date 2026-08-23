<!-- Content Wrapper. Contains page content -->
<section class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0">Actualizar usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>"><i class="fas fa-home"></i> Inicio</a>
                        </li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/users"><i class="fas fa-users"></i>
                                Usuarios</a></li>
                        <li class="breadcrumb-item active">Actualizar usuario</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- /.content-header -->

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <form id="userEditForm" action="<?= BASE_URL ?>/users/update" method="post" autocomplete="off">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" id="id_usuario" name="id_usuario" value="<?= $id_usuario; ?>">

                        <!-- Card 1: Información de la cuenta -->
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-user mr-1"></i> Información de la cuenta
                                    <span class="badge badge-secondary ml-1">ID #<?= $id_usuario ?></span>
                                </h2>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nombres">Nombres <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" id="nombres" name="nombres" class="form-control"
                                            value="<?= htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?>"
                                            autocomplete="off" aria-required="true">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="email">Correo electrónico <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i
                                                            class="fas fa-envelope"></i></span>
                                                </div>
                                                <input type="email" id="email" name="email" class="form-control"
                                                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                                                    autocomplete="off" aria-required="true">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="rol">Rol <span class="text-danger">*</span></label>
                                            <select id="rol" name="rol" class="form-control select2" aria-required="true">
                                                <?php foreach ($roles_datos as $roles_dato) :
                                                    $idRol = (int)$roles_dato['id_rol']; ?>
                                                    <option value="<?= $idRol; ?>" <?= $idRol === $idRolActual ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($roles_dato['rol'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Seguridad -->
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h2 class="card-title"><i class="fas fa-lock mr-1"></i> Seguridad</h2>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Cambio de contraseña <small>(opcional)</small></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_user">Nueva contraseña</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                </div>
                                                <input type="password" id="password_user" name="password_user"
                                                    class="form-control"
                                                    placeholder="Dejar en blanco para mantener actual"
                                                    autocomplete="new-password">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-default toggle-password"
                                                        aria-label="Mostrar contraseña">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="password_repeat">Confirmar nueva contraseña</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                </div>
                                                <input type="password" id="password_repeat" name="password_repeat"
                                                    class="form-control"
                                                    placeholder="Repita la contraseña" autocomplete="new-password">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-default toggle-password"
                                                        aria-label="Mostrar contraseña">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> Deje ambos campos vacíos si no desea cambiar la
                                    contraseña.
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                        <a href="<?= BASE_URL ?>/users" class="btn btn-default w-100">
                                            <i class="fas fa-times mr-1"></i> Cancelar
                                        </a>
                                    </div>
                                    <div class="col-12 col-sm-auto">
                                        <button type="submit" id="btnEditUser" class="btn btn-success w-100">
                                            <i class="fas fa-save mr-1"></i> Guardar cambios
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-md-4">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-id-badge"></i> Resumen</h2>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body box-profile">
                            <div class="text-center mb-3">
                                <i class="fas fa-user-circle fa-5x text-secondary"></i>
                            </div>
                            <h3 class="profile-username text-center"><?= htmlspecialchars($nombres, ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="text-muted text-center"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                            <ul class="list-group list-group-unbordered mb-0">
                                <li class="list-group-item">
                                    <b><i class="fas fa-hashtag text-primary"></i> ID</b>
                                    <span class="float-right badge badge-primary"><?= $id_usuario; ?></span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-user-tag text-info"></i> Rol actual</b>
                                    <span class="float-right"><?= htmlspecialchars($rolActual, ENT_QUOTES, 'UTF-8'); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h2 class="card-title"><i class="fas fa-lightbulb"></i> Recomendaciones</h2>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-sm text-muted mb-2">Valida que el correo sea único y vigente.</p>
                            <p class="text-sm text-muted mb-2">Cambia la contraseña solo cuando sea necesario.</p>
                            <p class="text-sm text-muted mb-0">Confirma que el rol asignado coincide con los permisos
                                esperados.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content-wrapper -->